<?php

namespace Tests\Feature;

use App\Models\BlockedUrl;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the URL shortening flow.
 *
 * Covers:
 *   - Happy path: valid URL → short code created and persisted.
 *   - Authentication: unauthenticated users are redirected to login.
 *   - Validation: invalid URLs and private/loopback URLs are rejected.
 *   - Deduplication: submitting the same URL twice reuses the existing link.
 */
class LinkShorteningTest extends TestCase
{
    use RefreshDatabase;

    // ── Happy Path ────────────────────────────────────────────────────────

    /**
     * An authenticated, verified user can shorten a valid HTTPS URL.
     * The response should redirect to the shorten page, and a Link record
     * with a non-empty short_code should be present in the database.
     */
    public function test_authenticated_user_can_shorten_a_valid_url(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/some/long/path',
        ]);

        $response->assertRedirect(route('links.index'));

        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://www.example.com/some/long/path',
        ]);

        $link = Link::where('user_id', $user->id)->first();
        $this->assertNotEmpty($link->short_code, 'short_code should not be empty after creation');
    }

    /**
     * Tracking parameters (like fbclid) should be stripped during normalization,
     * but UTM parameters should be preserved for the UTM builder.
     */
    public function test_tracking_params_are_stripped_during_normalization(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://example.com/page?utm_source=fb&utm_medium=cpc&fbclid=abc123&keep=this',
        ]);

        $link = Link::where('user_id', $user->id)->first();
        $this->assertNotNull($link, 'A link should have been created');
        $this->assertStringContainsString('utm_source=fb', $link->original_url);
        $this->assertStringNotContainsString('fbclid', $link->original_url);
        $this->assertStringContainsString('keep=this', $link->original_url);
    }

    /**
     * The generated short_code should be a valid Base62 string,
     * consisting only of alphanumeric characters.
     */
    public function test_generated_short_code_is_base62(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/base62test',
        ]);

        $link = Link::where('user_id', $user->id)->first();
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $link->short_code);
    }

    /**
     * A URL without a scheme should still be accepted, with https:// prepended.
     */
    public function test_url_without_scheme_is_accepted_with_https_injected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'www.example.com/no-scheme',
        ]);

        $response->assertRedirect(route('links.index'));
        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
        ]);

        $link = Link::where('user_id', $user->id)->first();
        $this->assertStringStartsWith('https://', $link->original_url);
    }

    // ── Authentication ────────────────────────────────────────────────────

    /**
     * Unauthenticated users hitting POST /links should be redirected to login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->post(route('links.store'), [
            'original_url' => 'https://www.example.com',
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Unauthenticated users hitting GET /links/shorten should be redirected to login.
     */
    public function test_unauthenticated_user_cannot_view_shorten_page(): void
    {
        $response = $this->get(route('links.index'));
        $response->assertRedirect(route('login'));
    }

    // ── Validation ────────────────────────────────────────────────────────

    /**
     * A plain string that is not a URL should be rejected with a validation error.
     */
    public function test_invalid_url_string_returns_validation_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'not-a-url-at-all!!!',
        ]);

        $response->assertSessionHasErrors(['original_url']);
        $this->assertDatabaseCount('links', 0);
    }

    /**
     * An empty URL field should fail required validation.
     */
    public function test_empty_url_returns_validation_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => '',
        ]);

        $response->assertSessionHasErrors(['original_url']);
    }

    /**
     * URLs targeting localhost should be blocked (SSRF prevention).
     */
    public function test_localhost_url_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'http://localhost/admin',
        ]);

        $response->assertSessionHasErrors(['original_url']);
        $this->assertDatabaseCount('links', 0);
    }

    /**
     * ftp:// URLs (non-http/https scheme) should be rejected.
     */
    public function test_ftp_url_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'ftp://files.example.com/data.zip',
        ]);

        $response->assertSessionHasErrors(['original_url']);
    }

    // ── Deduplication ─────────────────────────────────────────────────────

    /**
     * If a user submits the same URL twice, no new Link record should be created.
     * The response should redirect back and the session should carry a 'reused' indicator.
     */
    public function test_duplicate_url_returns_existing_link_without_creating_new_record(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/dedup-test',
        ]);

        $firstLink = Link::where('user_id', $user->id)->first();
        $this->assertNotNull($firstLink);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/dedup-test',
        ]);

        $response->assertRedirect(route('links.index'));
        $this->assertDatabaseCount('links', 1);

        // Flash should mark the link as reused
        $response->assertSessionHas('flash', function ($flash) use ($firstLink) {
            return $flash['reused'] === true
                && str_contains($flash['link'], $firstLink->short_code);
        });
    }

    /**
     * Two different users submitting the same URL should each get their own link.
     */
    public function test_two_users_shortening_same_url_get_separate_links(): void
    {
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);
        $url = 'https://www.example.com/shared-url';

        $this->actingAs($userA)->post(route('links.store'), ['original_url' => $url]);
        $this->actingAs($userB)->post(route('links.store'), ['original_url' => $url]);

        $this->assertDatabaseCount('links', 2);
    }

    // ── Shorten Page ──────────────────────────────────────────────────────

    /**
     * An authenticated user can view the shorten page (Inertia response).
     */
    public function test_authenticated_user_can_view_shorten_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('links.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Links/Shorten'));
    }

    // ── Custom Aliases ────────────────────────────────────────────────────────

    public function test_user_can_create_link_with_custom_alias(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/custom',
            'custom_alias' => 'my-custom-url',
        ]);

        $response->assertRedirect(route('links.index'));

        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://www.example.com/custom',
            'short_code' => 'my-custom-url',
            'is_custom_alias' => 1,
        ]);
    }

    public function test_custom_alias_must_be_unique(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Create an existing link
        Link::factory()->create([
            'short_code' => 'taken-alias',
            'is_custom_alias' => true,
        ]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/custom',
            'custom_alias' => 'taken-alias',
        ]);

        $response->assertSessionHasErrors(['custom_alias']);
    }

    public function test_reserved_words_cannot_be_used_as_custom_alias(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/api-test',
            'custom_alias' => 'api',
        ]);

        $response->assertSessionHasErrors(['custom_alias']);
    }

    public function test_deduplication_is_skipped_if_custom_alias_provided(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Create first link
        $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/duplicate',
        ]);

        // Create second link with SAME original_url but specific custom_alias
        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/duplicate',
            'custom_alias' => 'my-alias',
        ]);

        $response->assertRedirect(route('links.index'));

        // Should have two distinct links now
        $this->assertDatabaseCount('links', 2);

        $this->assertDatabaseHas('links', [
            'short_code' => 'my-alias',
        ]);
    }

    public function test_malicious_url_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Add a known malicious URL to the blocked_urls table
        BlockedUrl::factory()->create([
            'url_hash' => hash('sha256', 'https://www.example.com/malware'),
            'url' => 'https://www.example.com/malware',
        ]);

        $response = $this->actingAs($user)->post(route('links.store'), [
            'original_url' => 'https://www.example.com/malware',
        ]);

        $response->assertSessionHasErrors(['original_url']);
        $this->assertDatabaseMissing('links', [
            'original_url' => 'https://www.example.com/malware',
        ]);
    }
}
