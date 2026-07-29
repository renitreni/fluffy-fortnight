<?php

namespace Tests\Feature;

use App\Jobs\ProcessClickTracking;
use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;
use Tests\TestCase;

class ProcessClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_inserts_click_and_increments_count(): void
    {
        $link = Link::factory()->create([
            'click_count' => 0,
        ]);

        $position = new Position();
        $position->countryCode = 'US';
        $position->regionName = 'California';
        $position->cityName = 'San Francisco';
        $position->latitude = 37.7749;
        $position->longitude = -122.4194;

        Location::shouldReceive('get')
            ->with('192.168.1.1')
            ->once()
            ->andReturn($position);

        $job = new ProcessClickTracking(
            $link->id,
            '192.168.1.1',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
            'https://google.com/search?q=test',
            Carbon::now()->toIso8601String()
        );

        $job->handle();

        $this->assertDatabaseHas('clicks', [
            'link_id' => $link->id,
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
            'device_type' => 'mobile',
            'os' => 'iOS',
            'browser' => 'Safari',
            'country' => 'US',
            'region' => 'California',
            'city' => 'San Francisco',
            'referer' => 'https://google.com/search?q=test',
            'referer_domain' => 'google.com',
        ]);

        $this->assertEquals(1, $link->fresh()->click_count);
    }
}
