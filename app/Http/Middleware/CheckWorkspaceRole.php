<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWorkspaceRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->current_workspace_id) {
            return $next($request); // Not in a workspace context
        }

        $workspaceUser = \App\Models\WorkspaceUser::where('workspace_id', $user->current_workspace_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $workspaceUser || ! in_array($workspaceUser->role, $roles)) {
            abort(403, 'Unauthorized action for your role in this workspace.');
        }

        return $next($request);
    }
}
