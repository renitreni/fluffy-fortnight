<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessClickTracking;
use App\Models\Link;
use App\Services\RedirectCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Handles the public short-URL redirect endpoint.
 *
 * Route: GET /{shortCode}   (public, no auth required)
 *
 * ## Resolution Strategy
 *
 * 1. **Not-found tombstone check** — If Redis holds a `:notfound` key for
 *    this code, return 404 immediately without querying MySQL.
 * 2. **Cache hit** — If Redis holds link data, use it to perform the redirect
 *    without touching MySQL.
 * 3. **Cache miss** — Query MySQL for an active link:
 *      a. Found → warm the cache and redirect.
 *      b. Not found / soft-deleted → write tombstone, return 404.
 *
 * ## Redirect Semantics
 *
 *  - **302 Found** (default) — preserves analytics accuracy; browsers do NOT
 *    permanently cache the destination, allowing destination changes to take
 *    effect immediately for active campaigns.
 *  - **301 Moved Permanently** — reserved for future "permanent branded link"
 *    feature flag. Not emitted today.
 *
 * ## Edge Cases
 *
 *  - Inactive links (`is_active = false`): 404.
 *  - Expired links (`expires_at` in the past): 410 Gone.
 *  - Password-protected links: Render `Links/PasswordGate` Inertia page.
 *  - Unknown short codes: 404 (with tombstone written to Redis).
 */
class RedirectController extends Controller
{
    public function __construct(
        private readonly RedirectCacheService $cache,
    ) {}

    /**
     * Resolve a short code and redirect to the original URL.
     *
     * @param  string  $shortCode  The Base62 short code from the URL path.
     */
    public function __invoke(Request $request, string $shortCode): RedirectResponse|Response|\Inertia\Response
    {
        // ── 1. Not-found tombstone check ─────────────────────────────────────
        if ($this->cache->isMarkedNotFound($shortCode)) {
            return $this->notFoundResponse();
        }

        // ── 2. Cache hit ──────────────────────────────────────────────────────
        $cached = $this->cache->get($shortCode);

        if ($cached !== null) {
            if (!$this->isHostValid($request, $cached['expected_domain'] ?? null)) {
                return $this->notFoundResponse();
            }
            return $this->resolveFromPayload($request, $cached, $shortCode);
        }

        // ── 3. Cache miss → DB lookup ─────────────────────────────────────────
        /** @var Link|null $link */
        $link = Link::with('customDomain')->where('short_code', $shortCode)->first();

        // Not found or soft-deleted
        if ($link === null) {
            $this->cache->markNotFound($shortCode);

            return $this->notFoundResponse();
        }

        // Domain validation
        if (!$this->isHostValid($request, $link->customDomain?->domain)) {
            return $this->notFoundResponse();
        }

        // Inactive link
        if (! $link->is_active) {
            return $this->notFoundResponse();
        }

        // Expired link — 410 Gone (content existed but is now unavailable)
        if ($link->expires_at !== null && $link->expires_at->isPast()) {
            return response(null, 410);
        }

        // Password-protected link — show the gate page
        if ($link->password !== null) {
            // Warm the cache (including has_password=true) before rendering
            $this->cache->put($link);

            return $this->passwordGateResponse($shortCode);
        }

        // Warm the cache for subsequent requests
        $this->cache->put($link);

        // Social media crawlers get an OG preview page instead of a redirect
        if ($this->isSocialCrawler($request)) {
            return $this->ogPreviewResponse($link);
        }

        $userAgent = $request->userAgent() ?? '';
        $targetUrl = $link->original_url;

        if (! empty($link->ios_deep_link) && preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $targetUrl = $link->ios_deep_link;
        } elseif (! empty($link->android_deep_link) && preg_match('/Android/i', $userAgent)) {
            $targetUrl = $link->android_deep_link;
        }

        $this->dispatchTrackingJob($link->id, $request);

        return redirect()->away($targetUrl, 302);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the redirect from a cached payload.
     *
     * Checks activity and expiry from the cached data before redirecting, so
     * that a cache entry written just before a link was deactivated/expired
     * does not serve stale redirects past the cache TTL.
     *
     * @param  array{original_url: string, redirect_type: int, is_active: bool, expires_at: string|null, has_password: bool, ios_deep_link: string|null, android_deep_link: string|null}  $payload
     */
    private function resolveFromPayload(Request $request, array $payload, string $shortCode): RedirectResponse|Response|\Inertia\Response
    {
        if (! $payload['is_active']) {
            return $this->notFoundResponse();
        }

        if ($payload['expires_at'] !== null) {
            $expiresAt = Carbon::parse($payload['expires_at']);
            if ($expiresAt->isPast()) {
                return response(null, 410);
            }
        }

        // Password-protected — show the gate page (no password exposed to client)
        if (! empty($payload['has_password'])) {
            return $this->passwordGateResponse($shortCode);
        }

        // Social media crawlers get an OG preview page instead of a redirect
        if ($this->isSocialCrawler($request)) {
            return $this->ogPreviewResponseFromPayload($payload, $shortCode);
        }

        $statusCode = $payload['redirect_type'] ?? 302;
        $userAgent = $request->userAgent() ?? '';
        $targetUrl = $payload['original_url'];

        if (! empty($payload['ios_deep_link']) && preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $targetUrl = $payload['ios_deep_link'];
        } elseif (! empty($payload['android_deep_link']) && preg_match('/Android/i', $userAgent)) {
            $targetUrl = $payload['android_deep_link'];
        }

        $this->dispatchTrackingJob($payload['id'], $request);

        return redirect()->away($targetUrl, $statusCode);
    }

    /**
     * Render the Inertia password gate page for a password-protected short link.
     */
    private function passwordGateResponse(string $shortCode): \Inertia\Response
    {
        return Inertia::render('Links/PasswordGate', [
            'shortCode' => $shortCode,
        ]);
    }

    /**
     * Return a 404 response.
     *
     * Returns a plain HTTP 404 response for consistency across all
     * not-found scenarios in the redirect engine.
     */
    private function notFoundResponse(): Response
    {
        return response(null, 404);
    }

    /**
     * Dispatch the async click tracking job.
     */
    private function dispatchTrackingJob(int $linkId, Request $request): void
    {
        ProcessClickTracking::dispatch(
            $linkId,
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
            $request->header('referer'),
            now()->toIso8601String()
        );
    }

    /**
     * Validate that the incoming request host matches the expected domain.
     */
    private function isHostValid(Request $request, ?string $expectedDomain): bool
    {
        $host = $request->getHost();

        if ($expectedDomain !== null) {
            return $host === $expectedDomain;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.url');
        return $host === $appHost;
    }

    /**
     * Detect whether the request is from a social media crawler/bot.
     *
     * Matches user agents from Facebook, Twitter/X, LinkedIn, WhatsApp,
     * Telegram, Discord, Slack, and other platforms that scrape OG meta tags.
     */
    private function isSocialCrawler(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        $crawlers = [
            'facebookexternalhit',
            'facebot',
            'twitterbot',
            'linkedinbot',
            'whatsapp',
            'telegrambot',
            'discordbot',
            'slackbot',
            'googlebot',
            'bingbot',
            'applebot',
            'yandexbot',
            'duckduckbot',
            'baiduspider',
            'embedly',
            'quora link preview',
            'showyoubot',
            'outbrain',
            'pinterest',
            'vkshare',
            'w3c_validator',
            'redditbot',
            'flipboard',
            'tumblr',
            'skypeuripreview',
            'nuzzel',
            'qwantify',
        ];

        foreach ($crawlers as $crawler) {
            if (str_contains($userAgent, $crawler)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render an OG preview page for social media crawlers.
     *
     * Returns a Blade view with OpenGraph meta tags so that platforms
     * like Facebook, Twitter, LinkedIn display a rich preview card.
     * Regular users are auto-redirected via JavaScript.
     */
    private function ogPreviewResponse(Link $link): Response
    {
        $shortUrl = $link->customDomain
            ? 'https://' . $link->customDomain->domain . '/' . $link->short_code
            : rtrim(config('app.url'), '/') . '/' . $link->short_code;

        $ogImageUrl = null;
        $ogImageType = null;
        if ($link->og_image_path) {
            $ogImageUrl = url('storage/' . $link->og_image_path);
            $extension = pathinfo($link->og_image_path, PATHINFO_EXTENSION);
            $ogImageType = match (strtolower($extension)) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                default => 'image/jpeg',
            };
        }

        return response()->view('links.preview', [
            'title' => $link->title ?? $link->original_url,
            'description' => $link->description ?? 'Click to visit this link.',
            'ogImageUrl' => $ogImageUrl,
            'ogImageType' => $ogImageType,
            'shortUrl' => $shortUrl,
            'targetUrl' => $link->original_url,
        ]);
    }

    /**
     * Render an OG preview page from cached payload (cache-hit path).
     *
     * Avoids a DB round-trip by using the cached data directly.
     */
    private function ogPreviewResponseFromPayload(array $payload, string $shortCode): Response
    {
        $shortUrl = rtrim(config('app.url'), '/') . '/' . $shortCode;

        $ogImageUrl = null;
        $ogImageType = null;
        if (! empty($payload['og_image_path'])) {
            $ogImageUrl = url('storage/' . $payload['og_image_path']);
            $extension = pathinfo($payload['og_image_path'], PATHINFO_EXTENSION);
            $ogImageType = match (strtolower($extension)) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                default => 'image/jpeg',
            };
        }

        return response()->view('links.preview', [
            'title' => $payload['title'] ?? $payload['original_url'],
            'description' => $payload['description'] ?? 'Click to visit this link.',
            'ogImageUrl' => $ogImageUrl,
            'ogImageType' => $ogImageType,
            'shortUrl' => $shortUrl,
            'targetUrl' => $payload['original_url'],
        ]);
    }
}
