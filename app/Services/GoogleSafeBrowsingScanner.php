<?php

namespace App\Services;

use App\Models\BlockedUrl;
use App\Services\Contracts\MaliciousUrlScanner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSafeBrowsingScanner implements MaliciousUrlScanner
{
    /**
     * The URL for the Safe Browsing API v4.
     */
    private const API_URL = 'https://safebrowsing.googleapis.com/v4/threatMatches:find';

    public function isMalicious(string $url): bool
    {
        // 1. Check local blocked_urls table
        $urlHash = hash('sha256', $url);
        if (BlockedUrl::where('url_hash', $urlHash)->exists()) {
            return true;
        }

        // 2. Call Google Safe Browsing if key is configured
        $apiKey = config('services.google_safe_browsing.key');
        if (empty($apiKey)) {
            return false;
        }

        try {
            $response = Http::timeout(3)->post(self::API_URL.'?key='.$apiKey, [
                'client' => [
                    'clientId' => config('app.name'),
                    'clientVersion' => '1.0.0',
                ],
                'threatInfo' => [
                    'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE', 'POTENTIALLY_HARMFUL_APPLICATION'],
                    'platformTypes' => ['ANY_PLATFORM'],
                    'threatEntryTypes' => ['URL'],
                    'threatEntries' => [
                        ['url' => $url],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::warning('Google Safe Browsing API failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url,
                ]);

                return true; // Fail closed
            }

            // If there's a match, the response will have a 'matches' array
            $data = $response->json();

            return ! empty($data['matches']);
        } catch (\Exception $e) {
            Log::warning('Google Safe Browsing API exception.', [
                'message' => $e->getMessage(),
                'url' => $url,
            ]);

            return true; // Fail closed
        }
    }
}
