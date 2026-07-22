<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Support\Facades\Cache;

/**
 * Manages Redis-backed caching for the redirect engine.
 *
 * ## Cache Key Schema
 *
 *  link:{shortCode}          — Serialized link data (TTL: 24 hours)
 *  link:{shortCode}:notfound — Tombstone for confirmed-missing codes (TTL: 5 min)
 *
 * ## Purpose
 *
 * The redirect endpoint is the hottest path in the application. By caching
 * resolved link data in Redis we avoid a MySQL round-trip on every hit,
 * achieving sub-millisecond lookups after the first request.
 *
 * Tombstone entries prevent bot traffic or typos from hammering MySQL for
 * short codes that are known to not exist.
 *
 * ## Cache Payload
 *
 * Only the fields required for the redirect decision are stored; the full
 * Link model is NOT serialized to keep payloads small and avoid
 * deserialization coupling as the schema evolves.
 *
 * ```json
 * {
 *   "original_url":   "https://example.com/some/path",
 *   "redirect_type":  302,
 *   "is_active":      true,
 *   "expires_at":     "2026-12-31T23:59:59Z"   // null if no expiry
 * }
 * ```
 */
class RedirectCacheService
{
    /**
     * TTL for a cached active link (24 hours in seconds).
     */
    public const LINK_TTL = 86400;

    /**
     * TTL for a not-found tombstone (5 minutes in seconds).
     * Short enough to allow eventual consistency when a link is later created
     * with a previously-requested code, without hammering MySQL on every miss.
     */
    public const NOT_FOUND_TTL = 300;

    /**
     * Prefix used for all cache keys managed by this service.
     */
    private const KEY_PREFIX = 'link:';

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Retrieve cached redirect data for the given short code.
     *
     * @param  string $shortCode
     * @return array{original_url: string, redirect_type: int, is_active: bool, expires_at: string|null}|null
     *         Returns null on a cache miss.
     */
    public function get(string $shortCode): ?array
    {
        /** @var array|null $data */
        $data = Cache::get($this->linkKey($shortCode));

        return is_array($data) ? $data : null;
    }

    /**
     * Check whether the given short code has a not-found tombstone.
     *
     * @param  string $shortCode
     * @return bool
     */
    public function isMarkedNotFound(string $shortCode): bool
    {
        return Cache::has($this->notFoundKey($shortCode));
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Store redirect data for a Link in the cache.
     *
     * Only the fields necessary for redirect resolution are persisted.
     * The cache entry expires after {@see LINK_TTL} seconds.
     *
     * @param  \App\Models\Link $link
     * @return void
     */
    public function put(Link $link): void
    {
        $payload = [
            'original_url'  => $link->original_url,
            'redirect_type' => 302, // 301 support deferred to future feature flag
            'is_active'     => (bool) $link->is_active,
            'expires_at'    => $link->expires_at?->toIso8601String(),
        ];

        Cache::put($this->linkKey($link->short_code), $payload, self::LINK_TTL);
    }

    /**
     * Write a not-found tombstone for a short code that does not exist in the DB.
     *
     * Prevents repeated DB queries for the same invalid code during the
     * tombstone's TTL window.
     *
     * @param  string $shortCode
     * @return void
     */
    public function markNotFound(string $shortCode): void
    {
        Cache::put($this->notFoundKey($shortCode), true, self::NOT_FOUND_TTL);
    }

    // -------------------------------------------------------------------------
    // Invalidation
    // -------------------------------------------------------------------------

    /**
     * Remove the cached redirect data for a short code.
     *
     * Call this whenever a link is updated or deleted so that the next
     * redirect request performs a fresh DB lookup.
     *
     * @param  string $shortCode
     * @return void
     */
    public function forget(string $shortCode): void
    {
        Cache::forget($this->linkKey($shortCode));
        Cache::forget($this->notFoundKey($shortCode));
    }

    // -------------------------------------------------------------------------
    // Key Helpers
    // -------------------------------------------------------------------------

    /**
     * Build the cache key for link redirect data.
     *
     * @param  string $shortCode
     * @return string
     */
    public function linkKey(string $shortCode): string
    {
        return self::KEY_PREFIX . $shortCode;
    }

    /**
     * Build the cache key for a not-found tombstone.
     *
     * @param  string $shortCode
     * @return string
     */
    public function notFoundKey(string $shortCode): string
    {
        return self::KEY_PREFIX . $shortCode . ':notfound';
    }
}
