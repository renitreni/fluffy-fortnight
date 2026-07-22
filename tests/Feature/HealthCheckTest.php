<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HealthCheckTest
 *
 * Verifies that the /health endpoint:
 *  - Returns HTTP 200 when all dependencies are healthy.
 *  - Returns a JSON body conforming to the expected schema.
 *  - Is publicly accessible (no authentication required).
 *
 * The test environment uses SQLite in-memory (phpunit.xml) and the array
 * cache driver, so both database and cache checks should succeed.
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The health endpoint responds with HTTP 200 and the correct JSON shape.
     */
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
                'checks' => [
                    'database',
                    'redis',
                ],
            ])
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'ok');
    }

    /**
     * The health endpoint does not require an authenticated user.
     */
    public function test_health_endpoint_is_publicly_accessible(): void
    {
        // Make request without any auth guard
        $response = $this->getJson('/health');

        $response->assertStatus(200);
    }

    /**
     * The health endpoint returns JSON with the correct Content-Type header.
     */
    public function test_health_endpoint_returns_json_content_type(): void
    {
        $response = $this->getJson('/health');

        $response->assertHeader('Content-Type', 'application/json');
    }
}
