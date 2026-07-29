<?php

namespace Tests\Feature;

use App\Jobs\ProcessClickTracking;
use App\Models\Link;
use App\Models\User;
use App\Services\RedirectCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for password-protected links (Day 14).
 *
 * Covers:
 *   - Storing a link with a password → hashed in the DB.
 *   - Short password (< 4 chars) rejected.
 *   - Redirect to password-protected link → renders PasswordGate page.
 *   - Cached password-protected link → still renders PasswordGate.
 *   - POST correct password → 302 redirect to original URL.
 *   - POST wrong password → validation error.
 *   - POST to a non-existent short code → error.
 *   - POST to an expired password-protected link → error.
 */
class LinkPasswordProtectionTest extends TestCase
{
    use RefreshDatabase;

    // ── Storage ───────────────────────────────────────────────────────────────

    /**
     * A link created with a password should store a bcrypt hash, not plain text.
     */
    public function test_password_is_hashed_when_link_is_created(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('links.store'), [
                'original_url' => 'https://example.com/secret-page',
                'password' => 'SuperSecret123',
            ])
            ->assertRedirect(route('links.index'));

        $link = Link::where('user_id', $user->id)->first();

        $this->assertNotNull($link->password, 'Password column should not be null');

        // The raw value in the DB should be a bcrypt hash, not the plain text
        $rawHash = $link->getRawOriginal('password');
        $this->assertNotEquals('SuperSecret123', $rawHash, 'Password must not be stored as plain text');
        $this->assertTrue(Hash::check('SuperSecret123', $rawHash), 'Hash should verify against plain text');
    }

    /**
     * A password shorter than 4 characters is rejected by validation.
     */
    public function test_short_password_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('links.store'), [
                'original_url' => 'https://example.com/short-pass',
                'password' => 'abc',
            ])
            ->assertSessionHasErrors(['password']);
    }

    // ── Redirect Behaviour ────────────────────────────────────────────────────

    /**
     * Visiting a password-protected link should render the PasswordGate page
     * instead of issuing a redirect.
     */
    public function test_password_protected_link_renders_gate_page(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'pwlink1',
            'original_url' => 'https://www.secret.com/',
            'is_active' => true,
            'expires_at' => null,
            'password' => 'MyPassword1',
        ]);

        $response = $this->get('/'.$link->short_code);

        // Should be 200 (Inertia renders inline) not a redirect
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Links/PasswordGate'));
    }

    /**
     * A cached password-protected link should still render the gate page
     * (not bypass the password check).
     */
    public function test_cached_password_protected_link_still_renders_gate(): void
    {
        Cache::flush();

        $cacheService = app(RedirectCacheService::class);

        // Manually warm the cache with has_password = true
        Cache::put($cacheService->linkKey('pwcache1'), [
            'original_url' => 'https://www.secret-cached.com/',
            'redirect_type' => 302,
            'is_active' => true,
            'expires_at' => null,
            'has_password' => true,
        ], RedirectCacheService::LINK_TTL);

        $response = $this->get('/pwcache1');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Links/PasswordGate'));
    }

    // ── Password Verification ─────────────────────────────────────────────────

    /**
     * Submitting the correct password should redirect to the original URL.
     */
    public function test_correct_password_redirects_to_original_url(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'pwok1',
            'original_url' => 'https://www.destination.com/',
            'is_active' => true,
            'expires_at' => null,
            'password' => 'CorrectHorse',
        ]);

        Queue::fake();

        $response = $this->post(route('links.password-gate', $link->short_code), [
            'password' => 'CorrectHorse',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('https://www.destination.com/');

        Queue::assertPushed(ProcessClickTracking::class, function ($job) use ($link) {
            return $job->linkId === $link->id;
        });
    }

    /**
     * Submitting the wrong password should return an error without redirecting.
     */
    public function test_wrong_password_returns_error(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'pwfail1',
            'original_url' => 'https://www.secret-dest.com/',
            'is_active' => true,
            'expires_at' => null,
            'password' => 'CorrectPassword',
        ]);

        Queue::fake();

        $response = $this->post(route('links.password-gate', $link->short_code), [
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors(['password']);

        Queue::assertNotPushed(ProcessClickTracking::class);
    }

    /**
     * Submitting an empty password should fail required validation.
     */
    public function test_empty_password_fails_required_validation(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'pwempty1',
            'is_active' => true,
            'password' => 'SomePassword',
        ]);

        $this->post(route('links.password-gate', $link->short_code), [
            'password' => '',
        ])->assertSessionHasErrors(['password']);
    }

    /**
     * Posting a password to a non-existent short code returns an error.
     */
    public function test_password_gate_for_nonexistent_link_returns_error(): void
    {
        $response = $this->post(route('links.password-gate', 'GHOST99'), [
            'password' => 'anything',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * Posting a password to an expired link returns an expiry error.
     */
    public function test_password_gate_for_expired_link_returns_error(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $user->id,
            'short_code' => 'pwexp1',
            'original_url' => 'https://www.expired-secret.com/',
            'is_active' => true,
            'expires_at' => now()->subDay(),
            'password' => 'AnyPassword',
        ]);

        $response = $this->post(route('links.password-gate', $link->short_code), [
            'password' => 'AnyPassword',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
