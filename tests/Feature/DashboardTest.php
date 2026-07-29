<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_analytics_chart_data(): void
    {
        $user = \App\Models\User::factory()->create();
        $link = \App\Models\Link::factory()->create(['user_id' => $user->id]);

        \App\Models\ClickHourlySummary::factory()->create([
            'link_id' => $link->id,
            'hour' => now()->startOfDay(),
            'clicks' => 15,
        ]);

        $response = $this->actingAs($user)->get('/dashboard?range=7');

        $response->assertStatus(200);
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('chartData')
            ->has('chartData.labels')
            ->has('chartData.datasets')
        );
    }
}
