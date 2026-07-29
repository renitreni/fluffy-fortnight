<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the OAuth Provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        // Currently only supporting google
        if ($provider !== 'google') {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the Provider.
     */
    public function callback(string $provider): RedirectResponse
    {
        if ($provider !== 'google') {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['oauth' => 'Authentication failed.']);
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // If user exists but hasn't linked google_id, update it
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $socialUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }
        } else {
            // Create a new user
            $user = User::create([
                'name' => $socialUser->getName() ?? 'Google User',
                'email' => $socialUser->getEmail(),
                'google_id' => $socialUser->getId(),
                'password' => null, // Password can be null for SSO users
                'email_verified_at' => now(), // Assume Google verified their email
            ]);
        }

        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
