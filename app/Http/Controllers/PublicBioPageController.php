<?php

namespace App\Http\Controllers;

use App\Models\BioPage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicBioPageController extends Controller
{
    /**
     * Display the public bio page.
     */
    public function show(string $alias)
    {
        $bioPage = BioPage::where('alias', $alias)
            ->with(['links' => function ($query) {
                $query->where('is_active', true)
                      ->where(function ($q) {
                          $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                      })
                      ->orderBy('bio_page_link.display_order', 'asc');
            }])
            ->firstOrFail();

        // Use Inertia, but we'll instruct the frontend to use a blank layout
        return Inertia::render('Public/BioPage', [
            'bioPage' => $bioPage
        ]);
    }
}
