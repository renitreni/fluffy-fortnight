<?php

namespace App\Providers;

use App\Services\Contracts\MaliciousUrlScanner;
use App\Services\GoogleSafeBrowsingScanner;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            MaliciousUrlScanner::class,
            GoogleSafeBrowsingScanner::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api_key', function (Request $request) {
            return Limit::perMinute(120)->by($request->header('Authorization') ?: $request->ip());
        });

        RateLimiter::for('create_link', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
