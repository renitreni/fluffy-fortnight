<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\LinkController;
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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/links/shorten', [LinkController::class, 'index'])->name('links.index');
    Route::post('/links', [LinkController::class, 'store'])->name('links.store');
    Route::put('/links/{link}', [LinkController::class, 'update'])->name('links.update');
    Route::delete('/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
 * Public short-URL redirect endpoint — no auth, no CSRF.
 *
 * This MUST be declared last so it never shadows named routes (dashboard,
 * profile, health, etc.). The `where` constraint limits matching to valid
 * Base62 short codes; anything else falls through to Laravel's 404 handler.
 *
 * Redirect semantics: 302 Found (preserves analytics accuracy).
 */
Route::get('/{shortCode}', RedirectController::class)
    ->name('redirect')
    ->where('shortCode', '[0-9A-Za-z]+');
