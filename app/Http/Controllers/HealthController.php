<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * HealthController
 *
 * Provides a structured `/health` endpoint for load-balancer and monitoring
 * probes. Returns a 200 OK when all critical dependencies are reachable, or
 * a 503 Service Unavailable when any check fails.
 *
 * The response body follows a machine-readable JSON schema so that external
 * tools (Prometheus, Grafana, Datadog, etc.) can parse individual component
 * statuses without string-matching.
 */
class HealthController extends Controller
{
    /**
     * Perform health checks and return a structured JSON response.
     *
     * Checks performed:
     *  - database : Executes `SELECT 1` via the default DB connection.
     *  - redis    : Writes and reads a test key via the cache store.
     *
     * @return JsonResponse  200 when all checks pass, 503 on any failure.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $allHealthy = true;

        // ── Database check ────────────────────────────────────────────────
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (Throwable $e) {
            $checks['database'] = 'error';
            $allHealthy = false;
            Log::channel('stack')->error('Health check: database unreachable', [
                'error' => $e->getMessage(),
            ]);
        }

        // ── Redis / Cache check ────────────────────────────────────────────
        try {
            $key = 'health:probe';
            Cache::put($key, 'ok', 5);
            $value = Cache::get($key);

            if ($value !== 'ok') {
                throw new \RuntimeException('Cache read/write mismatch.');
            }

            $checks['redis'] = 'ok';
        } catch (Throwable $e) {
            $checks['redis'] = 'error';
            $allHealthy = false;
            Log::channel('stack')->error('Health check: redis unreachable', [
                'error' => $e->getMessage(),
            ]);
        }

        $status = $allHealthy ? 'ok' : 'degraded';
        $httpStatus = $allHealthy ? 200 : 503;

        $payload = [
            'status'    => $status,
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '0.5.0'),
            'checks'    => $checks,
        ];

        // Emit a single structured log line for observability pipelines.
        Log::channel('stack')->info('Health check performed', $payload);

        return response()->json($payload, $httpStatus);
    }
}
