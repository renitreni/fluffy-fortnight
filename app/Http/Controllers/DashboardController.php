<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard with links and stats.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // 1. Calculate stats
        $totalLinks = Link::forUser($user->id)->count();
        $totalClicks = (int) Link::forUser($user->id)->sum('click_count');
        $activeLinks = Link::forUser($user->id)->active()->count();
        $customAliases = Link::forUser($user->id)->where('is_custom_alias', true)->count();

        // 2. Fetch paginated and searched links
        $search = $request->query('search');

        $linksQuery = Link::forUser($user->id)->latest();

        if ($search) {
            $linksQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('short_code', 'like', '%'.$search.'%')
                    ->orWhere('original_url', 'like', '%'.$search.'%');
            });
        }

        $links = $linksQuery->paginate(10)->withQueryString();

        // 3. Fetch time-series chart data
        $range = $request->query('range', 30); // default 30 days
        $startDate = now()->subDays($range)->startOfDay();

        $linkIds = Link::forUser($user->id)->pluck('id');

        // Group by date, sum clicks
        $chartQuery = \App\Models\ClickHourlySummary::whereIn('link_id', $linkIds)
            ->where('hour', '>=', $startDate)
            ->selectRaw('DATE(hour) as date, SUM(clicks) as total_clicks')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates
        $chartLabels = [];
        $chartDataValues = [];
        
        $period = \Carbon\CarbonPeriod::create($startDate, now()->endOfDay());
        $chartQueryKeyed = $chartQuery->keyBy('date');

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $chartLabels[] = $date->format('M j');
            $chartDataValues[] = isset($chartQueryKeyed[$dateString]) ? (int) $chartQueryKeyed[$dateString]->total_clicks : 0;
        }

        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Clicks',
                    'data' => $chartDataValues,
                    'borderColor' => '#6366f1', // Indigo 500
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                ],
            ],
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                [
                    'label' => 'Total Links',
                    'value' => (string) $totalLinks,
                    'sub' => 'Start shortening URLs',
                    'icon' => '🔗',
                    'iconBg' => 'bg-brand-50 dark:bg-brand-900/30',
                    'iconColor' => '',
                ],
                [
                    'label' => 'Total Clicks',
                    'value' => (string) $totalClicks,
                    'sub' => 'Across all links',
                    'icon' => '📊',
                    'iconBg' => 'bg-emerald-50 dark:bg-emerald-900/30',
                    'iconColor' => '',
                ],
                [
                    'label' => 'Active Links',
                    'value' => (string) $activeLinks,
                    'sub' => 'Non-expired links',
                    'icon' => '✅',
                    'iconBg' => 'bg-amber-50 dark:bg-amber-900/30',
                    'iconColor' => '',
                ],
                [
                    'label' => 'Custom Aliases',
                    'value' => (string) $customAliases,
                    'sub' => 'Branded short links',
                    'icon' => '✨',
                    'iconBg' => 'bg-purple-50 dark:bg-purple-900/30',
                    'iconColor' => '',
                ],
            ],
            'links' => collect($links->items())->map(function ($link) {
                return [
                    'id' => $link->id,
                    'short_code' => $link->short_code,
                    'original_url' => $link->original_url,
                    'title' => $link->title,
                    'click_count' => $link->click_count,
                    'is_active' => $link->is_active,
                    'created_at' => $link->created_at,
                    'short_url' => rtrim(config('app.url'), '/').'/'.$link->short_code,
                ];
            }),
            'pagination' => [
                'current_page' => $links->currentPage(),
                'last_page' => $links->lastPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
                'links' => $links->linkCollection()->toArray(),
            ],
            'filters' => [
                'search' => $search,
                'range' => $range,
            ],
            'chartData' => $chartData,
        ]);
    }
}
