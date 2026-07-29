<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BulkShortenController;
use App\Http\Controllers\CustomDomainController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\PasswordGateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RedirectController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
 * Health check endpoint — publicly accessible, no auth or CSRF required.
 * Returns structured JSON: { status, timestamp, version, checks }.
 * Used by Docker health checks, load-balancers, and monitoring probes.
 */
Route::get('/health', HealthController::class)->name('health');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/links/shorten', [LinkController::class, 'index'])->name('links.index');
    Route::post('/links', [LinkController::class, 'store'])->middleware('throttle:create_link')->name('links.store');
    Route::put('/links/{link}', [LinkController::class, 'update'])->name('links.update');
    Route::delete('/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');
    Route::get('/links/{link}/analytics', [AnalyticsController::class, 'show'])->name('links.analytics');

    Route::resource('bio-pages', \App\Http\Controllers\BioPageController::class)->except(['show']);

    Route::get('/links/bulk', [BulkShortenController::class, 'index'])->name('bulk.index');
    Route::post('/links/bulk', [BulkShortenController::class, 'store'])->name('bulk.store');
    Route::get('/links/bulk/{bulkJob}/download', [BulkShortenController::class, 'download'])->name('bulk.download');

    Route::get('/custom-domains', [CustomDomainController::class, 'index'])->name('custom-domains.index');
    Route::post('/custom-domains', [CustomDomainController::class, 'store'])->name('custom-domains.store');
    Route::delete('/custom-domains/{customDomain}', [CustomDomainController::class, 'destroy'])->name('custom-domains.destroy');
    Route::post('/custom-domains/{customDomain}/verify', [CustomDomainController::class, 'verify'])->name('custom-domains.verify');

    Route::resource('workspaces', \App\Http\Controllers\WorkspaceController::class)->except(['create', 'edit']);
    Route::put('/current-workspace', [\App\Http\Controllers\WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::post('/workspaces/{workspace}/invitations', [\App\Http\Controllers\WorkspaceInvitationController::class, 'store'])->name('workspaces.invitations.store');
    Route::delete('/workspaces/{workspace}/invitations/{invitation}', [\App\Http\Controllers\WorkspaceInvitationController::class, 'destroy'])->name('workspaces.invitations.destroy');
    Route::delete('/workspaces/{workspace}/members/{user}', [\App\Http\Controllers\WorkspaceMemberController::class, 'destroy'])->name('workspaces.members.destroy');

    Route::get('/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{apiKey}', [\App\Http\Controllers\ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [\App\Http\Controllers\BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/portal', [\App\Http\Controllers\BillingController::class, 'portal'])->name('billing.portal');
});

Route::get('/invitations/{token}', [\App\Http\Controllers\WorkspaceInvitationController::class, 'accept'])->name('invitations.accept');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
 * Password gate verification — publicly accessible (no auth), CSRF protected (web middleware).
 *
 * Receives the submitted password for a password-protected short link,
 * verifies it, and redirects to the original URL on success.
 *
 * Defined here (in web.php with CSRF) rather than redirect.php (no middleware)
 * to ensure password verification is CSRF-protected.
 */
Route::post('/{shortCode}/auth', PasswordGateController::class)
    ->name('links.password-gate')
    ->where('shortCode', '[0-9A-Za-z_-]+');

/*
 * Public Link-in-Bio Route.
 */
Route::get('/b/{alias}', [\App\Http\Controllers\PublicBioPageController::class, 'show'])
    ->name('bio-pages.show')
    ->where('alias', '[0-9A-Za-z_-]+');

/*
 * Public short-URL redirect endpoint — no auth, no CSRF.
 *
 * This MUST be declared last so it never shadows named routes (dashboard,
 * profile, health, etc.). The `where` constraint limits matching to valid
 * Base62 short codes and custom aliases; anything else falls through to
 * Laravel's 404 handler.
 *
 * Note: redirect.php in bootstrap/app.php also registers GET /{shortCode}
 * without middleware for maximum performance. This declaration ensures a
 * named route exists in the web middleware context for URL generation.
 *
 * Redirect semantics: 302 Found (preserves analytics accuracy).
 */
Route::get('/{shortCode}', RedirectController::class)
    ->name('redirect')
    ->where('shortCode', '[0-9A-Za-z_-]+');
