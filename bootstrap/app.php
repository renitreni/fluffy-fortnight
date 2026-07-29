<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Short-URL redirect (GET /{shortCode}) is loaded with NO middleware
            // for maximum throughput. Redis caching keeps latency < 5ms on cache hits.
            // The password verification POST is in web.php where CSRF is enforced.
            Route::middleware([])->group(base_path('routes/redirect.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.api' => \App\Http\Middleware\VerifyApiKey::class,
            'plan' => \App\Http\Middleware\CheckSubscriptionPlan::class,
            'workspace.role' => \App\Http\Middleware\CheckWorkspaceRole::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
