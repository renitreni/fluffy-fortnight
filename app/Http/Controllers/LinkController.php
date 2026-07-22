<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLinkRequest;
use App\Models\Link;
use App\Services\RedirectCacheService;
use App\Services\ShortCodeGeneratorService;
use App\Services\UrlNormalizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles link shortening, listing, and CRUD operations.
 *
 * Routes:
 *   GET  /links/shorten  → index()    Renders the Inertia shorten page.
 *   POST /links          → store()    Accepts a long URL and returns a short code.
 *   PUT  /links/{link}   → update()   Updates link attributes (stubs; full logic in Day 8).
 *   DELETE /links/{link} → destroy()  Soft-deletes a link (stubs; full logic in Day 8).
 *
 * Auth + verified middleware is enforced at the route level.
 * Cache invalidation is wired here; Day 8 fills in the DB-mutation logic.
 */
class LinkController extends Controller
{
    public function __construct(
        private readonly UrlNormalizerService $normalizer,
        private readonly ShortCodeGeneratorService $generator,
        private readonly RedirectCacheService $redirectCache,
    ) {}

    /**
     * Display the URL shortening page.
     *
     * Passes the user's 5 most recently created links so they can be displayed
     * in the "recent links" section without a separate API call.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $recentLinks = Link::forUser($user->id)
            ->latest()
            ->take(5)
            ->get(['id', 'short_code', 'original_url', 'title', 'click_count', 'created_at']);

        return Inertia::render('Links/Shorten', [
            'recentLinks' => $recentLinks,
            'appUrl'      => rtrim(config('app.url'), '/'),
        ]);
    }

    /**
     * Shorten a submitted long URL.
     *
     * ## Flow:
     *   1. Normalize the URL (strip tracking params, canonicalize scheme/host).
     *   2. Check if this user already has a link for the normalized URL.
     *      If so, return the existing link with a reuse flash notice.
     *   3. Create a new Link record (without short_code) to obtain the DB id.
     *   4. Encode the id via Base62 to generate the short_code.
     *   5. Persist the short_code and redirect back with a success flash.
     *
     * @param  \App\Http\Requests\StoreLinkRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreLinkRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $normalizedUrl = $this->normalizer->normalize($request->validated('original_url'));

        // --- Deduplication: per-user, same normalized URL ---
        $existingLink = Link::forUser($user->id)
            ->where('original_url', $normalizedUrl)
            ->first();

        if ($existingLink !== null) {
            return redirect()->route('links.index')
                ->with('flash', [
                    'type'      => 'info',
                    'message'   => 'This URL was already shortened. Here is your existing short link.',
                    'link'      => $this->buildShortUrl($existingLink->short_code),
                    'shortCode' => $existingLink->short_code,
                    'reused'    => true,
                ]);
        }

        // --- Create within a transaction: use a temp placeholder for short_code ---
        // short_code is NOT NULL + UNIQUE, so we insert a unique UUID placeholder
        // to obtain the DB auto-increment id, then immediately replace it with the
        // Base62-encoded value. The whole operation is atomic.
        $link = DB::transaction(function () use ($user, $normalizedUrl, $request) {
            /** @var \App\Models\Link $newLink */
            $newLink = Link::create([
                'user_id'      => $user->id,
                'original_url' => $normalizedUrl,
                'title'        => $request->validated('title'),
                'is_active'    => true,
                'click_count'  => 0,
                'short_code'   => 'tmp_' . Str::uuid(), // temporary; replaced below
            ]);

            $shortCode = $this->generator->generateForLink($newLink);
            $newLink->short_code = $shortCode;

            return $newLink;
        });

        // Warm the Redis cache immediately so the first redirect is served
        // without a DB round-trip.
        $this->redirectCache->put($link);

        return redirect()->route('links.index')
            ->with('flash', [
                'type'      => 'success',
                'message'   => 'Your short link has been created!',
                'link'      => $this->buildShortUrl($link->short_code),
                'shortCode' => $link->short_code,
                'reused'    => false,
            ]);
    }

    /**
     * Update link attributes.
     *
     * Stub: DB-mutation logic implemented in Day 8. Cache invalidation is
     * wired here so Day 8 only needs to add the update logic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Link          $link
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Link $link): RedirectResponse
    {
        // TODO (Day 8): validate and apply attribute changes.

        // Evict the cache so the next redirect performs a fresh DB lookup.
        $this->redirectCache->forget($link->short_code);

        return redirect()->route('links.index')
            ->with('flash', [
                'type'    => 'success',
                'message' => 'Link updated successfully.',
            ]);
    }

    /**
     * Soft-delete a link.
     *
     * Stub: full authorization and response logic implemented in Day 8.
     * Cache invalidation is wired here so stale Redis entries do not serve
     * redirects after deletion.
     *
     * @param  \App\Models\Link $link
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Link $link): RedirectResponse
    {
        // Evict from cache before soft-deleting so concurrent requests
        // cannot sneak a redirect through after deletion.
        $this->redirectCache->forget($link->short_code);

        // TODO (Day 8): authorize, then soft-delete.
        $link->delete();

        return redirect()->route('links.index')
            ->with('flash', [
                'type'    => 'success',
                'message' => 'Link deleted.',
            ]);
    }

    /**
     * Build the full short URL from a short code.
     *
     * @param  string $shortCode
     * @return string
     */
    private function buildShortUrl(string $shortCode): string
    {
        return rtrim(config('app.url'), '/') . '/' . $shortCode;
    }
}
