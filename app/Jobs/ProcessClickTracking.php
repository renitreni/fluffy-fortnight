<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class ProcessClickTracking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $linkId,
        public string $ipAddress,
        public ?string $userAgent,
        public ?string $referer,
        public string $clickedAt
    ) {}

    public function handle(): void
    {
        $link = Link::with('user')->find($this->linkId);
        $anonymize = $link && $link->user ? $link->user->ip_anonymization : false;

        $refererDomain = null;
        if ($this->referer) {
            $parsed = parse_url($this->referer);
            if (isset($parsed['host'])) {
                $refererDomain = strtolower($parsed['host']);
            }
        }

        // Device parsing
        $agent = new Agent();
        if ($this->userAgent) {
            $agent->setUserAgent($this->userAgent);
        }

        $deviceType = 'unknown';
        if ($agent->isRobot()) {
            $deviceType = 'bot';
        } elseif ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif ($agent->isDesktop()) {
            $deviceType = 'desktop';
        }

        $os = $agent->platform() ?: null;
        $browser = $agent->browser() ?: null;

        // GeoIP parsing
        $country = null;
        $region = null;
        $city = null;
        $latitude = null;
        $longitude = null;

        if ($this->ipAddress && $this->ipAddress !== '127.0.0.1' && $this->ipAddress !== '0.0.0.0') {
            $position = Location::get($this->ipAddress);
            if ($position) {
                $country = $position->countryCode;
                
                if (!$anonymize) {
                    $region = $position->regionName;
                    $city = $position->cityName;
                    $latitude = $position->latitude;
                    $longitude = $position->longitude;
                }
            }
        }

        // Hash IP for GDPR compliance before persisting (skip if anonymized)
        $ipHash = $anonymize ? null : hash('sha256', $this->ipAddress);

        DB::transaction(function () use (
            $refererDomain, $ipHash, $deviceType, $os, $browser,
            $country, $region, $city, $latitude, $longitude
        ) {
            DB::table('clicks')->insert([
                'link_id' => $this->linkId,
                'ip_hash' => $ipHash,
                'country' => $country,
                'region' => $region,
                'city' => $city,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'device_type' => $deviceType,
                'os' => $os,
                'browser' => $browser,
                'user_agent' => $this->userAgent,
                'referer' => $this->referer,
                'referer_domain' => $refererDomain,
                'clicked_at' => Carbon::parse($this->clickedAt)->format('Y-m-d H:i:s'),
            ]);

            Link::where('id', $this->linkId)->increment('click_count');
        });
    }
}
