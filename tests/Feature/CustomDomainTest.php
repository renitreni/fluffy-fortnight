<?php

namespace Tests\Feature;

use App\Models\CustomDomain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_custom_domains_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/custom-domains');

        $response->assertStatus(200);
    }

    public function test_user_can_add_custom_domain()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/custom-domains', [
            'domain' => 'link.brand.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('custom_domains', [
            'user_id' => $user->id,
            'domain' => 'link.brand.com',
            'is_verified' => false,
        ]);
    }

    public function test_user_cannot_add_duplicate_custom_domain()
    {
        $user = User::factory()->create();
        
        CustomDomain::factory()->create([
            'domain' => 'link.brand.com',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/custom-domains', [
            'domain' => 'link.brand.com',
        ]);

        $response->assertSessionHasErrors('domain');
    }

    public function test_user_can_delete_their_custom_domain()
    {
        $user = User::factory()->create();
        
        $domain = CustomDomain::factory()->create([
            'user_id' => $user->id,
            'domain' => 'link.brand.com',
        ]);

        $response = $this->actingAs($user)->delete('/custom-domains/' . $domain->id);

        $response->assertRedirect();
        $this->assertSoftDeleted('custom_domains', ['id' => $domain->id]);
    }

    public function test_user_cannot_delete_others_custom_domain()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $domain = CustomDomain::factory()->create([
            'user_id' => $otherUser->id,
            'domain' => 'link.otherbrand.com',
        ]);

        $response = $this->actingAs($user)->delete('/custom-domains/' . $domain->id);

        $response->assertStatus(403);
    }

    public function test_link_can_be_assigned_to_custom_domain()
    {
        $user = User::factory()->create();
        
        $domain = CustomDomain::factory()->create([
            'user_id' => $user->id,
            'domain' => 'link.brand.com',
            'is_verified' => true,
        ]);

        $response = $this->actingAs($user)->post('/links', [
            'original_url' => 'https://example.com/some/long/path',
            'custom_domain_id' => $domain->id,
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://example.com/some/long/path',
            'custom_domain_id' => $domain->id,
        ]);
    }

    public function test_redirect_enforces_custom_domain_match()
    {
        $user = User::factory()->create();
        
        $domain = CustomDomain::factory()->create([
            'user_id' => $user->id,
            'domain' => 'link.mybrand.com',
            'is_verified' => true,
        ]);

        $link = Link::factory()->create([
            'user_id' => $user->id,
            'custom_domain_id' => $domain->id,
            'original_url' => 'https://example.com',
            'short_code' => 'xyz123',
            'is_active' => true,
        ]);

        // Request with correct host
        $response = $this->get('http://link.mybrand.com/xyz123');
        $response->assertStatus(302);
        $response->assertRedirect('https://example.com');

        // Clear cache so it hits DB again
        Cache::flush();

        // Request with wrong host
        $response = $this->get('http://wrong.domain.com/xyz123');
        $response->assertStatus(404);
        
        // Also ensure the cache handles it. Hit it with right domain, cache it.
        $this->get('http://link.mybrand.com/xyz123');
        
        // Now hit with wrong domain while cached
        $response = $this->get('http://wrong.domain.com/xyz123');
        $response->assertStatus(404);
    }
}
