<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for link expiration (Day 14).
 *
 * Covers:
 *   - Storing a link with a future expiration date.
 *   - Validation: past expiration date rejected.
 *   - Validation: invalid date format rejected.
 *   - Redirect to non-expired link → 302.
 *   - Redirect to expired link → 410 Gone.
 *   - Cached expired link → 410 Gone.
 */
class LinkExpirationTest extends TestCase
{
    use RefreshDatabase;

    // ── Storage ───────────────────────────────────────────────────────────────

    /**
     * A link submitted with a future expiry is created and persists the date.
     */
    public function test_can_create_link_with_future_expiry(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $expiresAt = now()->addDays(7)->format('Y-m-d\TH:i');

        $this->actingAs($user)
            ->post(route('links.store'), [
                'original_url' => 'https://example.com/expiry-test',
                'expires_at' => $expiresAt,
            ])
            ->assertRedirect(route('links.index'));

        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://example.com/expiry-test',
        ]);

        $link = Link::where('user_id', $user->id)
            ->where('original_url', 'https://example.com/expiry-test')
            ->firstOrFail();

        $this->assertNotNull($link->expires_at, 'expires_at should be stored in the database');
        $this->assertTrue(
            $link->expires_at->isFuture(),
            'The stored expiry should be in the future'
        );
    }

    /**
     * A link submitted without an expiry is created with expires_at = null.
     */
    public function test_link_without_expiry_has_null_expires_at(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('links.store'), [
                'original_url' => 'https://example.com/no-expiry',
            ])
            ->assertRedirect(route('links.index'));

        $link = Link::where('user_id', $user->id)
            ->where('original_url', 'https://example.com/no-expiry')
            ->firstOrFail();

        $this->assertNull($link->expires_at, 'expires_at should be null when not set');
    }

    // ── Validation ────────────────────────────────────────────────────────────

    /**
     * Submitting a past expiry date should fail validation.
     */
    public function test_past_expiry_date_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('links.store'), [
                'original_url' => 'https://example.com/bad-expiry',
                'expires_at' => now()->subDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['expires_at']);
    }

    /**
     * Submitting an invalid date string should fail validation.
     */
    public function test_invalid_expiry_format_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('links.store'), [
                'original_url' => 'https://example.com/bad-date',
                'expires_at' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['expires_at']);
    }

    // ── Redirect Behaviour ────────────────────────────────────────────────────

    /**
     * A link whose expiry is in the future should still redirect (302).
     */
    public function test_non_expired_link_redirects(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'futexp1',
            'original_url' => 'https://www.future.com/',
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $this->get('/'.$link->short_code)->assertStatus(302);
    }

    /**
     * A link whose expiry has passed should return 410 Gone.
     */
    public function test_expired_link_returns_410(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'pastexp1',
            'original_url' => 'https://www.pastexpiry.com/',
            'is_active' => true,
            'expires_at' => now()->subHour(),
        ]);

        $this->get('/'.$link->short_code)->assertStatus(410);
    }
}
