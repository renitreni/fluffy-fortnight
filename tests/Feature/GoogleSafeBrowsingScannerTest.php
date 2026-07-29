<?php

namespace Tests\Feature;

use App\Models\BlockedUrl;
use App\Services\GoogleSafeBrowsingScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleSafeBrowsingScannerTest extends TestCase
{
    use RefreshDatabase;

    private GoogleSafeBrowsingScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new GoogleSafeBrowsingScanner;
    }

    public function test_it_returns_true_if_url_is_in_local_blocked_list(): void
    {
        $url = 'https://example.com/bad';
        BlockedUrl::factory()->create([
            'url_hash' => hash('sha256', $url),
            'url' => $url,
        ]);

        $this->assertTrue($this->scanner->isMalicious($url));
    }

    public function test_it_returns_false_if_api_key_is_missing_and_not_in_local_list(): void
    {
        Config::set('services.google_safe_browsing.key', null);

        $this->assertFalse($this->scanner->isMalicious('https://example.com/good'));
    }

    public function test_it_calls_google_api_and_returns_true_if_match_found(): void
    {
        Config::set('services.google_safe_browsing.key', 'fake-key');

        Http::fake([
            'safebrowsing.googleapis.com/*' => Http::response([
                'matches' => [
                    ['threatType' => 'MALWARE', 'platformType' => 'ANY_PLATFORM'],
                ],
            ], 200),
        ]);

        $this->assertTrue($this->scanner->isMalicious('https://example.com/bad'));
    }

    public function test_it_calls_google_api_and_returns_false_if_no_match(): void
    {
        Config::set('services.google_safe_browsing.key', 'fake-key');

        Http::fake([
            'safebrowsing.googleapis.com/*' => Http::response([], 200), // No matches array
        ]);

        $this->assertFalse($this->scanner->isMalicious('https://example.com/good'));
    }

    public function test_it_fails_closed_returns_true_if_api_request_fails(): void
    {
        Config::set('services.google_safe_browsing.key', 'fake-key');

        Http::fake([
            'safebrowsing.googleapis.com/*' => Http::response([], 500),
        ]);

        $this->assertTrue($this->scanner->isMalicious('https://example.com/maybe-bad'));
    }
}
