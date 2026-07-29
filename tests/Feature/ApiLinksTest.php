<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthHeaders(User $user)
    {
        $rawKey = 'sk_test_key_123';
        ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_hash' => hash('sha256', $rawKey),
            'is_active' => true,
        ]);

        return ['Authorization' => 'Bearer ' . $rawKey];
    }

    public function test_api_can_list_links()
    {
        $user = User::factory()->create();
        Link::factory()->count(3)->create(['user_id' => $user->id, 'workspace_id' => null]);

        $response = $this->withHeaders($this->getAuthHeaders($user))
            ->getJson('/api/v1/links');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_api_can_create_link()
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->getAuthHeaders($user))
            ->postJson('/api/v1/links', [
                'long_url' => 'https://example.com/api-test',
                'title' => 'API Link',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'API Link');
            
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://example.com/api-test',
            'title' => 'API Link',
        ]);
    }

    public function test_api_rejects_unauthorized()
    {
        $response = $this->getJson('/api/v1/links');

        $response->assertStatus(401);
    }
}
