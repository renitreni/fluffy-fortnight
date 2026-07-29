<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkspaceInvitationController extends Controller
{
    public function store(\Illuminate\Http\Request $request, \App\Models\Workspace $workspace)
    {
        if ($workspace->owner_id !== auth()->id()) {
            abort(403, 'Only the owner can invite members.');
        }

        $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        if ($workspace->members()->where('email', $request->email)->exists() || $workspace->owner->email === $request->email) {
            return back()->withErrors(['email' => 'User is already a member of this workspace.']);
        }

        $invitation = $workspace->invitations()->updateOrCreate(
            ['email' => $request->email],
            ['role' => $request->role, 'token' => \Illuminate\Support\Str::random(40)]
        );

        \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\WorkspaceInvitationMail($invitation));

        return back()->with('success', 'Invitation sent successfully.');
    }

    public function accept($token)
    {
        $invitation = \App\Models\WorkspaceInvitation::where('token', $token)->firstOrFail();

        if (!auth()->check()) {
            session()->put('url.intended', route('invitations.accept', $token));
            return redirect()->route('register')->with('info', 'Please create an account to accept the invitation.');
        }

        if (auth()->user()->email !== $invitation->email) {
            abort(403, 'This invitation is for a different email address.');
        }

        $invitation->workspace->members()->attach(auth()->id(), [
            'role' => $invitation->role,
            'joined_at' => now(),
        ]);

        $invitation->delete();

        auth()->user()->switchWorkspace($invitation->workspace);

        return redirect()->route('dashboard')->with('success', "You have joined the {$invitation->workspace->name} workspace.");
    }

    public function destroy(\App\Models\Workspace $workspace, \App\Models\WorkspaceInvitation $invitation)
    {
        if ($workspace->owner_id !== auth()->id()) {
            abort(403, 'Only the owner can cancel invitations.');
        }

        if ($invitation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled.');
    }
}
