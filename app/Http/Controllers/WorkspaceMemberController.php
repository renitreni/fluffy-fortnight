<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function destroy(\App\Models\Workspace $workspace, \App\Models\User $user)
    {
        if ($workspace->owner_id !== auth()->id()) {
            abort(403, 'Only the owner can remove members.');
        }

        if ($workspace->owner_id === $user->id) {
            abort(403, 'Cannot remove the owner from the workspace.');
        }

        $workspace->members()->detach($user->id);

        if ($user->current_workspace_id === $workspace->id) {
            $user->update(['current_workspace_id' => null]);
        }

        return back()->with('success', 'Member removed.');
    }
}
