<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLinkRequest;
use App\Models\Link;
use App\Models\User;
use App\Services\RedirectCacheService;
use App\Services\ShortCodeGeneratorService;
use App\Services\UrlNormalizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles link shortening, listing, and CRUD operations.
 *
 * Routes:
 *   GET  /links/shorten        → index()    Renders the Inertia shorten page.
 *   POST /links                → store()    Accepts a long URL and returns a short code.
 *   PUT  /links/{link}         → update()   Updates link attributes.
 *   DELETE /links/{link}       → destroy()  Soft-deletes a link.
 *
 * Auth + verified middleware is enforced at the route level.
 * Cache invalidation is wired here to evict stale Redis entries on mutation.
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
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $recentLinks = Link::where('workspace_id', $user->current_workspace_id)
            ->when(!$user->current_workspace_id, fn ($q) => $q->forUser($user->id))
            ->latest()
            ->take(5)
            ->get(['id', 'short_code', 'original_url', 'title', 'click_count', 'created_at']);
            
        $customDomainsQuery = $user->current_workspace_id
            ? $user->currentWorkspace->customDomains()
            : $user->customDomains()->whereNull('workspace_id');

        $customDomains = $customDomainsQuery
            ->where('is_verified', true)
            ->get(['id', 'domain']);

        return Inertia::render('Links/Shorten', [
            'recentLinks' => $recentLinks,
            'appUrl' => rtrim(config('app.url'), '/'),
            'customDomains' => $customDomains,
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
     */
    public function store(StoreLinkRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $normalizedUrl = $this->normalizer->normalize($request->validated('original_url'));

        // --- Deduplication: per-workspace/user, same normalized URL ---
        // Skip deduplication if the user explicitly provided a custom alias.
        $existingLink = null;
        if (! $request->filled('custom_alias')) {
            $existingLink = Link::where('original_url', $normalizedUrl)
                ->where('workspace_id', $user->current_workspace_id)
                ->when(!$user->current_workspace_id, fn ($q) => $q->forUser($user->id))
                ->first();
        }

        if ($existingLink !== null) {
            $existingLink->load('customDomain');
            return redirect()->route('links.index')
                ->with('flash', [
                    'type' => 'info',
                    'message' => 'This URL was already shortened. Here is your existing short link.',
                    'link' => $this->buildShortUrl($existingLink),
                    'shortCode' => $existingLink->short_code,
                    'reused' => true,
                ]);
        }

        // --- Create within a transaction: use a temp placeholder for short_code ---
        // short_code is NOT NULL + UNIQUE, so we insert a unique UUID placeholder
        // to obtain the DB auto-increment id, then immediately replace it with the
        // Base62-encoded value. The whole operation is atomic.
        // If a custom alias is provided, we use it directly without a placeholder.
        $link = DB::transaction(function () use ($user, $normalizedUrl, $request) {
            $customAlias = $request->validated('custom_alias');

            /** @var Link $newLink */
            $newLink = Link::create([
                'user_id' => $user->id,
                'workspace_id' => $user->current_workspace_id,
                'original_url' => $normalizedUrl,
                'title' => $request->validated('title') ?? '',
                'description' => $request->validated('description'),
                'is_active' => true,
                'click_count' => 0,
                'short_code' => $customAlias ?? 'tmp_'.substr(Str::uuid()->toString(), 0, 15),
                'is_custom_alias' => $customAlias !== null,
                'expires_at' => $request->validated('expires_at'),
                'password' => $request->validated('password'),
                'custom_domain_id' => $request->validated('custom_domain_id'),
            ]);

            if ($customAlias === null) {
                $this->generator->generateForLink($newLink);
            }

            // Handle OG image upload after link creation so we can use the link ID in the path
            if ($request->hasFile('og_image')) {
                $path = Storage::disk('public')->putFile(
                    'og_images',
                    $request->file('og_image')
                );
                $newLink->update(['og_image_path' => $path]);
            }

            return $newLink;
        });

        // Warm the Redis cache immediately so the first redirect is served
        // without a DB round-trip.
        $link->load('customDomain');
        $this->redirectCache->put($link);

        return redirect()->route('links.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Your short link has been created!',
                'link' => $this->buildShortUrl($link),
                'shortCode' => $link->short_code,
                'reused' => false,
            ]);
    }

    /**
     * Update link attributes.
     *
     * Stub: DB-mutation logic implemented in Day 8. Cache invalidation is
     * wired here so Day 8 only needs to add the update logic.
     */
    public function update(Request $request, Link $link): RedirectResponse
    {
        $user = $request->user();
        $canEdit = $link->user_id === $user->id || ($link->workspace_id && $user->isMemberOf($link->workspace));
        abort_unless($canEdit, 403);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_og_image' => ['nullable', 'boolean'],
        ]);

        // Handle OG image removal
        if (! empty($validated['remove_og_image']) && $link->og_image_path) {
            Storage::disk('public')->delete($link->og_image_path);
            $validated['og_image_path'] = null;
        }

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            // Delete old image if exists
            if ($link->og_image_path) {
                Storage::disk('public')->delete($link->og_image_path);
            }
            $validated['og_image_path'] = Storage::disk('public')->putFile(
                'og_images',
                $request->file('og_image')
            );
        }

        unset($validated['og_image'], $validated['remove_og_image']);

        if (array_key_exists('title', $validated) && $validated['title'] === null) {
            $validated['title'] = '';
        }

        $link->update($validated);

        // Evict the cache so the next redirect performs a fresh DB lookup.
        $this->redirectCache->forget($link->short_code);

        return redirect()->back()
            ->with('flash', [
                'type' => 'success',
                'message' => 'Link updated successfully.',
            ]);
    }

    /**
     * Soft-delete a link.
     *
     * Stub: full authorization and response logic implemented in Day 8.
     * Cache invalidation is wired here so stale Redis entries do not serve
     * redirects after deletion.
     */
    public function destroy(Request $request, Link $link): RedirectResponse
    {
        $user = $request->user();
        $canDelete = $link->user_id === $user->id || ($link->workspace_id && $user->isMemberOf($link->workspace));
        abort_unless($canDelete, 403);

        // Evict from cache before soft-deleting so concurrent requests
        // cannot sneak a redirect through after deletion.
        $this->redirectCache->forget($link->short_code);

        // Clean up uploaded OG image if present
        if ($link->og_image_path) {
            Storage::disk('public')->delete($link->og_image_path);
        }

        $link->delete();

        return redirect()->back()
            ->with('flash', [
                'type' => 'success',
                'message' => 'Link deleted.',
            ]);
    }

    /**
     * Build the full short URL from a link.
     */
    private function buildShortUrl(Link $link): string
    {
        $host = $link->customDomain ? 'https://' . $link->customDomain->domain : rtrim(config('app.url'), '/');
        return $host.'/'.$link->short_code;
    }
}
