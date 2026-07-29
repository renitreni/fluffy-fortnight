<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming API request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid Bearer token.'
            ], 401);
        }

        // The stored hash is a SHA-256 of the raw token
        $keyHash = hash('sha256', $token);

        $apiKey = ApiKey::where('key_hash', $keyHash)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or revoked API key.'
            ], 401);
        }

        // Update last used timestamp
        $apiKey->update(['last_used_at' => now()]);

        // Authenticate the user for this request
        Auth::login($apiKey->user);

        // Scope the request to the workspace if applicable
        if ($apiKey->workspace_id) {
            // Note: In a stateless API context, setting it on the user object for the duration 
            // of the request might be enough, or adding it to request attributes.
            $apiKey->user->current_workspace_id = $apiKey->workspace_id;
        }

        // Inject the apiKey instance into the request for downstream use if needed
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
