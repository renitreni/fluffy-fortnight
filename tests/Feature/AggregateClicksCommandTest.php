<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AggregateClicksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_clicks_into_hourly_summaries()
    {
        \Illuminate\Support\Facades\Cache::forget('analytics_last_click_id');

        $user = \App\Models\User::factory()->create();
        $workspace = \App\Models\Workspace::factory()->create(['owner_id' => $user->id]);
        $link = \App\Models\Link::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        // Create 3 clicks in the same hour with same dimensions
        $time1 = now()->startOfHour()->addMinutes(5);
        for ($i = 0; $i < 3; $i++) {
            \App\Models\Click::factory()->create([
                'link_id' => $link->id,
                'country' => 'US',
                'device_type' => 'desktop',
                'os' => 'Windows',
                'browser' => 'Chrome',
                'referer_domain' => 'google.com',
                'clicked_at' => $time1,
            ]);
        }

        // Create 2 clicks in the same hour with different dimension (mobile)
        for ($i = 0; $i < 2; $i++) {
            \App\Models\Click::factory()->create([
                'link_id' => $link->id,
                'country' => 'US',
                'device_type' => 'mobile',
                'os' => 'iOS',
                'browser' => 'Safari',
                'referer_domain' => 'google.com',
                'clicked_at' => $time1,
            ]);
        }

        // Create 1 click in a different hour
        $time2 = now()->subHour()->startOfHour();
        \App\Models\Click::factory()->create([
            'link_id' => $link->id,
            'country' => 'GB',
            'device_type' => 'desktop',
            'os' => 'macOS',
            'browser' => 'Safari',
            'referer_domain' => null,
            'clicked_at' => $time2,
        ]);

        $this->artisan('analytics:aggregate-clicks')
             ->expectsOutputToContain('Aggregated 6 clicks')
             ->assertExitCode(0);

        // Verify the aggregations
        $this->assertDatabaseCount('click_hourly_summaries', 3);

        $this->assertDatabaseHas('click_hourly_summaries', [
            'link_id' => $link->id,
            'hour' => $time1->format('Y-m-d H:00:00'),
            'device_type' => 'desktop',
            'clicks' => 3,
        ]);

        $this->assertDatabaseHas('click_hourly_summaries', [
            'link_id' => $link->id,
            'hour' => $time1->format('Y-m-d H:00:00'),
            'device_type' => 'mobile',
            'clicks' => 2,
        ]);

        $this->assertDatabaseHas('click_hourly_summaries', [
            'link_id' => $link->id,
            'hour' => $time2->format('Y-m-d H:00:00'),
            'country' => 'GB',
            'clicks' => 1,
        ]);

        // Test incremental run
        $this->artisan('analytics:aggregate-clicks')
             ->expectsOutput('No new clicks to aggregate.')
             ->assertExitCode(0);

        // Verify it didn't duplicate
        $this->assertDatabaseCount('click_hourly_summaries', 3);
    }
}
