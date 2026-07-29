<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LinkManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_links_and_stats(): void
    {
        $user = User::factory()->create();
        Link::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('links', 3)
                ->has('stats', 4)
                ->has('pagination.links')
                ->has('filters.search')
            );
    }

    public function test_dashboard_search_filters_links(): void
    {
        $user = User::factory()->create();
        Link::factory()->create(['user_id' => $user->id, 'title' => 'Alpha Project']);
        Link::factory()->create(['user_id' => $user->id, 'title' => 'Beta Marketing']);

        $response = $this->actingAs($user)->get(route('dashboard', ['search' => 'Alpha']));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('links', 1)
                ->where('links.0.title', 'Alpha Project')
            );
    }

    public function test_user_can_update_their_link(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id, 'title' => 'Old Title', 'is_active' => true]);

        $response = $this->actingAs($user)->put(route('links.update', $link), [
            'title' => 'New Title',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'title' => 'New Title',
            'is_active' => false,
        ]);
    }

    public function test_user_cannot_update_others_link(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user2->id, 'title' => 'Old Title']);

        $response = $this->actingAs($user1)->put(route('links.update', $link), [
            'title' => 'New Title',
            'is_active' => true,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'title' => 'Old Title',
        ]);
    }

    public function test_user_can_delete_their_link(): void
    {
        $user = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('links.destroy', $link));

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');

        $this->assertSoftDeleted('links', [
            'id' => $link->id,
        ]);
    }

    public function test_user_cannot_delete_others_link(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $link = Link::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->delete(route('links.destroy', $link));

        $response->assertStatus(403);

        $this->assertNotSoftDeleted('links', [
            'id' => $link->id,
        ]);
    }
}
