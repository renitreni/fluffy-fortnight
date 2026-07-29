<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Display detailed analytics for a specific link.
     */
    public function show(Request $request, Link $link): Response
    {
        // Ensure user owns this link
        if ($link->user_id !== $request->user()->id) {
            abort(403);
        }

        // Clicks by Country
        $countries = DB::table('clicks')
            ->select('country', DB::raw('count(*) as total'))
            ->where('link_id', $link->id)
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Clicks by Referrer
        $referrers = DB::table('clicks')
            ->select('referer_domain', DB::raw('count(*) as total'))
            ->where('link_id', $link->id)
            ->groupBy('referer_domain')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'domain' => $item->referer_domain ?: 'Direct / Unknown',
                    'total' => $item->total,
                ];
            });

        // Clicks by Device Type
        $devices = DB::table('clicks')
            ->select('device_type', DB::raw('count(*) as total'))
            ->where('link_id', $link->id)
            ->groupBy('device_type')
            ->orderByDesc('total')
            ->get();

        // Clicks by Browser
        $browsers = DB::table('clicks')
            ->select('browser', DB::raw('count(*) as total'))
            ->where('link_id', $link->id)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Clicks by OS
        $os = DB::table('clicks')
            ->select('os', DB::raw('count(*) as total'))
            ->where('link_id', $link->id)
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return Inertia::render('Links/Analytics', [
            'link' => [
                'id' => $link->id,
                'title' => $link->title,
                'short_code' => $link->short_code,
                'original_url' => $link->original_url,
                'click_count' => $link->click_count,
                'short_url' => rtrim(config('app.url'), '/') . '/' . $link->short_code,
            ],
            'stats' => [
                'countries' => $countries,
                'referrers' => $referrers,
                'devices' => $devices,
                'browsers' => $browsers,
                'os' => $os,
            ],
        ]);
    }
}
