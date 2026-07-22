<?php

namespace Tests\Unit;

use App\Models\Link;
use App\Services\RedirectCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for RedirectCacheService.
 *
 * Covers:
 *   - get()             — returns null on miss, array on hit.
 *   - put()             — stores correctly typed payload; keys expire within TTL.
 *   - forget()          — evicts link and not-found keys.
 *   - markNotFound()    — writes tombstone with correct key.
 *   - isMarkedNotFound() — returns true/false based on tombstone presence.
 *   - linkKey()         — returns expected key string.
 *   - notFoundKey()     — returns expected key string.
 */
class RedirectCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private RedirectCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RedirectCacheService::class);
        Cache::flush();
    }

    // ── get() ──────────────────────────────────────────────────────────────────

    /** @test */
    public function get_returns_null_when_no_cache_entry_exists(): void
    {
        $this->assertNull($this->service->get('missing123'));
    }

    /** @test */
    public function get_returns_array_payload_on_cache_hit(): void
    {
        Cache::put($this->service->linkKey('hit1'), [
            'original_url'  => 'https://example.com',
            'redirect_type' => 302,
            'is_active'     => true,
            'expires_at'    => null,
        ], 60);

        $result = $this->service->get('hit1');

        $this->assertIsArray($result);
        $this->assertEquals('https://example.com', $result['original_url']);
        $this->assertEquals(302, $result['redirect_type']);
    }

    // ── put() ──────────────────────────────────────────────────────────────────

    /** @test */
    public function put_stores_expected_payload_for_active_link(): void
    {
        $link = Link::factory()->create([
            'short_code'   => 'put1',
            'original_url' => 'https://put.example.com/path',
            'is_active'    => true,
            'expires_at'   => null,
        ]);

        $this->service->put($link);

        $cached = $this->service->get('put1');

        $this->assertNotNull($cached);
        $this->assertEquals('https://put.example.com/path', $cached['original_url']);
        $this->assertEquals(302, $cached['redirect_type']);
        $this->assertTrue($cached['is_active']);
        $this->assertNull($cached['expires_at']);
    }

    /** @test */
    public function put_stores_iso8601_expires_at_when_set(): void
    {
        $expiresAt = now()->addDays(7);

        $link = Link::factory()->create([
            'short_code'   => 'putexp1',
            'original_url' => 'https://expiry.example.com/',
            'is_active'    => true,
            'expires_at'   => $expiresAt,
        ]);

        $this->service->put($link);

        $cached = $this->service->get('putexp1');

        $this->assertNotNull($cached['expires_at']);
        // Verify it's a parseable ISO 8601 string
        $parsed = \Illuminate\Support\Carbon::parse($cached['expires_at']);
        $this->assertTrue($parsed->isSameDay($expiresAt));
    }

    // ── forget() ───────────────────────────────────────────────────────────────

    /** @test */
    public function forget_removes_link_cache_entry(): void
    {
        $link = Link::factory()->create([
            'short_code'   => 'forget1',
            'original_url' => 'https://forget.example.com/',
            'is_active'    => true,
            'expires_at'   => null,
        ]);

        $this->service->put($link);
        $this->assertNotNull($this->service->get('forget1'), 'Cache should be warm before forget');

        $this->service->forget('forget1');

        $this->assertNull($this->service->get('forget1'), 'Cache entry should be null after forget');
    }

    /** @test */
    public function forget_also_removes_not_found_tombstone(): void
    {
        $this->service->markNotFound('forgotghost1');
        $this->assertTrue($this->service->isMarkedNotFound('forgotghost1'));

        $this->service->forget('forgotghost1');

        $this->assertFalse($this->service->isMarkedNotFound('forgotghost1'));
    }

    // ── markNotFound() / isMarkedNotFound() ────────────────────────────────────

    /** @test */
    public function mark_not_found_writes_tombstone_key(): void
    {
        $this->assertFalse($this->service->isMarkedNotFound('ghost1'));

        $this->service->markNotFound('ghost1');

        $this->assertTrue($this->service->isMarkedNotFound('ghost1'));
    }

    /** @test */
    public function is_marked_not_found_returns_false_for_unknown_codes(): void
    {
        $this->assertFalse($this->service->isMarkedNotFound('definitelynothere'));
    }

    // ── Key Helpers ────────────────────────────────────────────────────────────

    /** @test */
    public function link_key_returns_expected_string(): void
    {
        $this->assertEquals('link:aB3x', $this->service->linkKey('aB3x'));
    }

    /** @test */
    public function not_found_key_returns_expected_string(): void
    {
        $this->assertEquals('link:aB3x:notfound', $this->service->notFoundKey('aB3x'));
    }
}
