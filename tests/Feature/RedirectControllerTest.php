<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use App\Services\RedirectCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature tests for the public redirect endpoint.
 *
 * Covers:
 *   - 302 redirect for valid active links.
 *   - Cache warming on first hit (DB miss → Redis write).
 *   - Cache-served redirects without DB queries.
 *   - 404 for unknown, inactive, or missing short codes.
 *   - Tombstone caching for not-found codes.
 *   - 410 Gone for expired links.
 *   - Cache eviction on link deletion.
 */
class RedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Happy Path ─────────────────────────────────────────────────────────────

    /**
     * A valid active short code should result in a 302 redirect to the original URL.
     */
    public function test_valid_short_code_redirects_to_original_url(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id'      => $user->id,
            'short_code'   => 'abc1',
            'original_url' => 'https://www.example.com/target-page',
            'is_active'    => true,
            'expires_at'   => null,
        ]);

        $response = $this->get('/' . $link->short_code);

        $response->assertStatus(302);
        $response->assertRedirect('https://www.example.com/target-page');
    }

    // ── Cache Warming ──────────────────────────────────────────────────────────

    /**
     * After the first DB hit the redirect data should be written to Redis.
     */
    public function test_redirect_warms_cache_on_db_hit(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id'      => $user->id,
            'short_code'   => 'warm1',
            'original_url' => 'https://www.cacheme.com/',
            'is_active'    => true,
            'expires_at'   => null,
        ]);

        // First hit — cache is empty
        $this->get('/' . $link->short_code)->assertStatus(302);

        // The cache service should now have the entry
        $cacheService = app(RedirectCacheService::class);
        $cached = $cacheService->get($link->short_code);

        $this->assertNotNull($cached, 'Cache entry should be written after first DB hit');
        $this->assertEquals('https://www.cacheme.com/', $cached['original_url']);
        $this->assertEquals(302, $cached['redirect_type']);
        $this->assertTrue($cached['is_active']);
    }

    /**
     * When the cache is warm, the redirect should not issue any DB queries.
     */
    public function test_cached_redirect_does_not_query_database(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id'      => $user->id,
            'short_code'   => 'nodb1',
            'original_url' => 'https://www.nodedb.com/',
            'is_active'    => true,
            'expires_at'   => null,
        ]);

        // Warm the cache via the first request
        $this->get('/' . $link->short_code)->assertStatus(302);

        // Count DB queries on the second request
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $response = $this->get('/' . $link->short_code);
        $response->assertStatus(302);

        $this->assertEquals(0, $queryCount, 'No DB queries should be made when cache is warm');
    }

    // ── Not-Found Handling ─────────────────────────────────────────────────────

    /**
     * A completely unknown short code should return 404.
     */
    public function test_unknown_short_code_returns_404(): void
    {
        Cache::flush();

        $response = $this->get('/NOTEXIST');

        $response->assertStatus(404);
    }

    /**
     * After a not-found DB miss, a tombstone key should be written to Redis.
     * The next request for the same code should return 404 without hitting the DB.
     */
    public function test_unknown_short_code_is_tombstone_cached(): void
    {
        Cache::flush();

        $this->get('/ghost1')->assertStatus(404);

        $cacheService = app(RedirectCacheService::class);
        $this->assertTrue(
            $cacheService->isMarkedNotFound('ghost1'),
            'A not-found tombstone should be written to Redis after a DB miss'
        );

        // Second request should serve 404 from tombstone without hitting the DB
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->get('/ghost1')->assertStatus(404);

        $this->assertEquals(0, $queryCount, 'Tombstone should prevent DB query on repeated 404');
    }

    // ── Inactive Links ─────────────────────────────────────────────────────────

    /**
     * A link with `is_active = false` should return 404.
     */
    public function test_inactive_link_returns_404(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id'    => $user->id,
            'short_code' => 'inact1',
            'is_active'  => false,
            'expires_at' => null,
        ]);

        $this->get('/' . $link->short_code)->assertStatus(404);
    }

    // ── Expired Links ──────────────────────────────────────────────────────────

    /**
     * A link past its `expires_at` timestamp should return 410 Gone.
     */
    public function test_expired_link_returns_410_gone(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id'    => $user->id,
            'short_code' => 'exp1',
            'is_active'  => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->get('/' . $link->short_code)->assertStatus(410);
    }

    /**
     * A cached expired link should also return 410 — the cache payload is
     * checked for expiry on every request so updates take effect without
     * waiting for TTL expiry.
     */
    public function test_cached_expired_link_returns_410_gone(): void
    {
        Cache::flush();

        $cacheService = app(RedirectCacheService::class);

        // Manually warm the cache with an expired payload
        $cacheService->get('expCache1'); // ensure empty first
        Cache::put($cacheService->linkKey('expCache1'), [
            'original_url'  => 'https://www.expired.com/',
            'redirect_type' => 302,
            'is_active'     => true,
            'expires_at'    => now()->subHour()->toIso8601String(),
        ], RedirectCacheService::LINK_TTL);

        $this->get('/expCache1')->assertStatus(410);
    }

    // ── Cache Invalidation ─────────────────────────────────────────────────────

    /**
     * Deleting a link via the controller should evict its cache entry so
     * subsequent redirects do not serve stale data.
     */
    public function test_cache_invalidated_on_link_delete(): void
    {
        Cache::flush();

        $user  = User::factory()->create(['email_verified_at' => now()]);
        $link  = Link::factory()->create([
            'user_id'    => $user->id,
            'short_code' => 'del1',
            'is_active'  => true,
            'expires_at' => null,
        ]);

        // Warm the cache
        $cacheService = app(RedirectCacheService::class);
        $cacheService->put($link);
        $this->assertNotNull($cacheService->get($link->short_code));

        // Destroy via controller
        $this->actingAs($user)
            ->delete(route('links.destroy', $link))
            ->assertRedirect(route('links.index'));

        // Cache entry should be evicted
        $this->assertNull(
            $cacheService->get($link->short_code),
            'Cache entry must be evicted after link deletion'
        );
    }
}
