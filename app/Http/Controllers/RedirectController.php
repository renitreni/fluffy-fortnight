<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\RedirectCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * @param  \Illuminate\Http\Request $request
     * @param  string                   $shortCode  The Base62 short code from the URL path.
     * @return \Illuminate\Http\RedirectResponse|\Inertia\Response|\Illuminate\Http\Response
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
            return $this->resolveFromPayload($cached);
        }

        // ── 3. Cache miss → DB lookup ─────────────────────────────────────────
        /** @var \App\Models\Link|null $link */
        $link = Link::where('short_code', $shortCode)->first();

        // Not found or soft-deleted
        if ($link === null) {
            $this->cache->markNotFound($shortCode);

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

        // Warm the cache for subsequent requests
        $this->cache->put($link);

        return redirect()->away($link->original_url, 302);
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
     * @param  array{original_url: string, redirect_type: int, is_active: bool, expires_at: string|null} $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    private function resolveFromPayload(array $payload): RedirectResponse|Response
    {
        if (! $payload['is_active']) {
            return $this->notFoundResponse();
        }

        if ($payload['expires_at'] !== null) {
            $expiresAt = \Illuminate\Support\Carbon::parse($payload['expires_at']);
            if ($expiresAt->isPast()) {
                return response(null, 410);
            }
        }

        $statusCode = $payload['redirect_type'] ?? 302;

        return redirect()->away($payload['original_url'], $statusCode);
    }

    /**
     * Return a 404 response.
     *
     * Returns a plain HTTP 404 response for consistency across all
     * not-found scenarios in the redirect engine.
     *
     * @return \Illuminate\Http\Response
     */
    private function notFoundResponse(): Response
    {
        return response(null, 404);
    }
}
