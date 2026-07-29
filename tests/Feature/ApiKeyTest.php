<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_api_keys()
    {
        $user = User::factory()->create();
        ApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('api-keys.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_create_api_key()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('api-keys.store'), [
            'name' => 'My API Key',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.apiKey');

        $this->assertDatabaseHas('api_keys', [
            'user_id' => $user->id,
            'name' => 'My API Key',
        ]);
    }

    public function test_user_can_revoke_api_key()
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('api-keys.destroy', $apiKey));

        $response->assertRedirect();
        $this->assertDatabaseMissing('api_keys', [
            'id' => $apiKey->id,
        ]);
    }
}
