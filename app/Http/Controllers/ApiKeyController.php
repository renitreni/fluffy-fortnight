<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of the user's API keys.
     */
    public function index(Request $request): Response
    {
        $apiKeys = $request->user()
            ->apiKeys()
            ->latest()
            ->get();

        return Inertia::render('Settings/ApiKeys', [
            'apiKeys' => $apiKeys,
        ]);
    }

    /**
     * Store a newly created API key in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $rawKey = 'sk_' . Str::random(40);
        
        $request->user()->apiKeys()->create([
            'name' => $validated['name'],
            'key_hash' => hash('sha256', $rawKey),
            'key_prefix' => substr($rawKey, 0, 8),
            'workspace_id' => $request->user()->current_workspace_id,
            'is_active' => true,
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Key created successfully. Please copy it now, you will not be able to see it again.',
            'apiKey' => $rawKey,
        ]);
    }

    /**
     * Revoke the specified API key.
     */
    public function destroy(Request $request, ApiKey $apiKey): RedirectResponse
    {
        if ($apiKey->user_id !== $request->user()->id) {
            abort(403);
        }

        $apiKey->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Key revoked successfully.',
        ]);
    }
}
