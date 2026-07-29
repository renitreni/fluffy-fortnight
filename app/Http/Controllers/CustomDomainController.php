<?php

namespace App\Http\Controllers;

use App\Models\CustomDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomDomainController extends Controller
{
    /**
     * Display a listing of the user's custom domains.
     */
    public function index(Request $request): Response
    {
        $domains = $request->user()
            ->customDomains()
            ->latest()
            ->get();

        return Inertia::render('CustomDomains/Index', [
            'domains' => $domains,
            // Pass the target IP or CNAME for instructions
            'dnsTarget' => config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'cname.url-shortener.com',
        ]);
    }

    /**
     * Store a newly created custom domain in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253', 'unique:custom_domains,domain'],
        ]);

        $domainStr = strtolower(trim($validated['domain']));
        
        // Basic validation for domain format
        if (!preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,63}$/', $domainStr)) {
            return back()->withErrors(['domain' => 'The domain format is invalid.']);
        }

        $request->user()->customDomains()->create([
            'domain' => $domainStr,
            'verification_token' => 'agy-verify-' . Str::random(32),
            'is_verified' => false,
            'ssl_status' => 'pending',
            // Default to personal workspace if no active workspace handling yet
            'workspace_id' => $request->user()->current_workspace_id ?? null,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Custom domain added. Please configure your DNS settings to verify ownership.'
        ]);
    }

    /**
     * Verify DNS ownership via TXT record.
     */
    public function verify(Request $request, CustomDomain $customDomain): RedirectResponse
    {
        // Ensure user owns this domain
        if ($customDomain->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($customDomain->is_verified) {
            return back()->with('toast', [
                'type' => 'info',
                'message' => 'Domain is already verified.'
            ]);
        }

        $records = @dns_get_record($customDomain->domain, DNS_TXT);
        
        $verified = false;
        if ($records) {
            foreach ($records as $record) {
                if (isset($record['txt']) && str_contains($record['txt'], $customDomain->verification_token)) {
                    $verified = true;
                    break;
                }
            }
        }

        // For local development, if dns_get_record fails, we might want a backdoor or just fail.
        // I'll stick to actual DNS. We can mock dns_get_record in tests.

        if ($verified) {
            $customDomain->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);

            return back()->with('toast', [
                'type' => 'success',
                'message' => 'Domain verified successfully! You can now use it for your short links.'
            ]);
        }

        return back()->with('toast', [
            'type' => 'error',
            'message' => 'Verification failed. Please ensure the TXT record is propagated (this may take a few minutes).'
        ]);
    }

    /**
     * Remove the specified custom domain from storage.
     */
    public function destroy(Request $request, CustomDomain $customDomain): RedirectResponse
    {
        if ($customDomain->user_id !== $request->user()->id) {
            abort(403);
        }

        $customDomain->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Custom domain deleted.'
        ]);
    }
}
