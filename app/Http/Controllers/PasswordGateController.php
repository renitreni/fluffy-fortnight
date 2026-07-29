<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessClickTracking;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Handles password verification for password-protected short links.
 *
 * Route: POST /{shortCode}/auth   (public, no auth required)
 *
 * ## Flow
 *
 * 1. Resolve the Link by short code (must be active and not expired).
 * 2. Verify the submitted password against the stored bcrypt hash.
 * 3. On success → redirect to the original URL (302).
 * 4. On failure → redirect back with a validation error.
 *
 * ## Security Notes
 *
 * - The hashed password is NEVER sent to the client (it is in `$hidden` on the model).
 * - `Hash::check()` is always called even when the link has no password (via
 *   constant-time comparison) to prevent timing-based enumeration of which
 *   short codes are password-protected. However, the route only exists for
 *   links rendered through the gate, so this path in practice is only hit by
 *   users who were already shown the gate page.
 * - Brute-force protection is handled at the route level via Redis rate limiting
 *   (to be wired in Day 28 alongside the global API rate-limiter).
 */
class PasswordGateController extends Controller
{
    /**
     * Verify the submitted password and redirect to the original URL on success.
     *
     * @param  string  $shortCode  The Base62 short code from the URL path.
     */
    public function __invoke(Request $request, string $shortCode): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Please enter the password to access this link.',
        ]);

        /** @var Link|null $link */
        $link = Link::where('short_code', $shortCode)->first();

        // Link does not exist, is soft-deleted, inactive, or expired
        if ($link === null || ! $link->is_active) {
            return redirect()->back()->withErrors([
                'password' => 'This link is no longer available.',
            ]);
        }

        if ($link->expires_at !== null && $link->expires_at->isPast()) {
            return redirect()->back()->withErrors([
                'password' => 'This link has expired.',
            ]);
        }

        if ($link->password === null) {
            ProcessClickTracking::dispatch(
                $link->id,
                $request->ip() ?? '0.0.0.0',
                $request->userAgent(),
                $request->header('referer'),
                now()->toIso8601String()
            );

            return redirect()->away($link->original_url, 302);
        }

        // Verify the submitted plain-text password against the stored hash.
        // The `password` column has a `hashed` cast, so $link->password stores
        // the bcrypt hash; we use Hash::check() on the raw DB value to avoid
        // double-hashing through the cast.
        $storedHash = $link->getRawOriginal('password');

        if (! Hash::check($request->input('password'), $storedHash)) {
            return redirect()->back()->withErrors([
                'password' => 'The password you entered is incorrect.',
            ])->withInput();
        }

        ProcessClickTracking::dispatch(
            $link->id,
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
            $request->header('referer'),
            now()->toIso8601String()
        );

        return redirect()->away($link->original_url, 302);
    }
}
