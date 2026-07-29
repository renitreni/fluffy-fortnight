<?php

namespace App\Http\Controllers;

use App\Models\BioPage;
use App\Models\Link;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class BioPageController extends Controller
{
    public function index(Request $request)
    {
        $bioPages = $request->user()->bioPages()->latest()->paginate(10);
        return Inertia::render('BioPages/Index', [
            'bioPages' => $bioPages
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('BioPages/Form', [
            'availableLinks' => $request->user()->links()->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'alias' => ['required', 'string', 'max:255', 'unique:bio_pages,alias'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'theme' => ['required', 'in:light,dark,brand'],
            'links' => ['array'],
            'links.*' => ['exists:links,id'],
        ]);

        $bioPage = $request->user()->bioPages()->create($validated);
        
        if (!empty($validated['links'])) {
            $syncData = [];
            foreach ($validated['links'] as $index => $linkId) {
                $syncData[$linkId] = ['display_order' => $index];
            }
            $bioPage->links()->sync($syncData);
        }

        return redirect()->route('bio-pages.index')->with('success', 'Bio page created successfully.');
    }

    public function edit(Request $request, BioPage $bioPage)
    {
        if ($bioPage->user_id !== $request->user()->id) {
            abort(403);
        }
        
        $bioPage->load(['links' => function($q) {
            $q->orderBy('bio_page_link.display_order', 'asc');
        }]);

        return Inertia::render('BioPages/Form', [
            'bioPage' => $bioPage,
            'availableLinks' => $request->user()->links()->latest()->get()
        ]);
    }

    public function update(Request $request, BioPage $bioPage)
    {
        if ($bioPage->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'alias' => ['required', 'string', 'max:255', Rule::unique('bio_pages')->ignore($bioPage->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'theme' => ['required', 'in:light,dark,brand'],
            'links' => ['array'],
            'links.*' => ['exists:links,id'],
        ]);

        $bioPage->update($validated);

        if (isset($validated['links'])) {
            $syncData = [];
            foreach ($validated['links'] as $index => $linkId) {
                $syncData[$linkId] = ['display_order' => $index];
            }
            $bioPage->links()->sync($syncData);
        } else {
            $bioPage->links()->detach();
        }

        return redirect()->route('bio-pages.index')->with('success', 'Bio page updated successfully.');
    }

    public function destroy(Request $request, BioPage $bioPage)
    {
        if ($bioPage->user_id !== $request->user()->id) {
            abort(403);
        }
        $bioPage->delete();
        return redirect()->route('bio-pages.index')->with('success', 'Bio page deleted successfully.');
    }
}
