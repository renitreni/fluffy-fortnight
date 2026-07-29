<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

/*
 * Public short-URL redirect endpoint — no auth, no CSRF, no session.
 *
 * Loaded without the 'web' middleware group to maximize RPS.
 * Redirect semantics: 302 Found (preserves analytics accuracy).
 *
 * Password-protected links render the Inertia PasswordGate page. The Inertia
 * response works without the full web middleware stack because the controller
 * only needs the Inertia response helper (no session required for the GET).
 * The password verification POST lives in web.php where CSRF is enforced.
 */
Route::get('/{shortCode}', RedirectController::class)
    ->name('links.redirect')
    ->where('shortCode', '[0-9A-Za-z_-]+');
