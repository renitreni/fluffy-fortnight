<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index()
    {
        $workspaces = auth()->user()->workspaces()->withPivot('role')->get()
            ->merge(auth()->user()->ownedWorkspaces);

        return inertia('Workspaces/Index', [
            'workspaces' => $workspaces,
        ]);
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $workspace = auth()->user()->ownedWorkspaces()->create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name) . '-' . uniqid(),
            'custom_domain_limit' => 1,
        ]);

        auth()->user()->switchWorkspace($workspace);

        return redirect()->route('workspaces.show', $workspace)->with('success', 'Workspace created and activated.');
    }

    public function show(\App\Models\Workspace $workspace)
    {
        if (!auth()->user()->isMemberOf($workspace)) {
            abort(403);
        }

        return inertia('Workspaces/Show', [
            'workspace' => $workspace->load(['members', 'owner', 'invitations']),
        ]);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Workspace $workspace)
    {
        if ($workspace->owner_id !== auth()->id()) {
            abort(403, 'Only the owner can update the workspace.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $workspace->update(['name' => $request->name]);

        return back()->with('success', 'Workspace updated.');
    }

    public function destroy(\App\Models\Workspace $workspace)
    {
        if ($workspace->owner_id !== auth()->id()) {
            abort(403, 'Only the owner can delete the workspace.');
        }

        $workspace->delete();

        if (auth()->user()->current_workspace_id === $workspace->id) {
            auth()->user()->update(['current_workspace_id' => null]);
        }

        return redirect()->route('dashboard')->with('success', 'Workspace deleted.');
    }

    public function switch(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'workspace_id' => 'nullable|exists:workspaces,id',
        ]);

        if ($request->workspace_id) {
            $workspace = \App\Models\Workspace::findOrFail($request->workspace_id);
            auth()->user()->switchWorkspace($workspace);
            return back()->with('success', "Switched to {$workspace->name}.");
        }

        auth()->user()->update(['current_workspace_id' => null]);
        return back()->with('success', "Switched to personal workspace.");
    }
}
