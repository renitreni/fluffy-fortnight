<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'workspaces' => $request->user() ? $request->user()->workspaces()->get()->merge($request->user()->ownedWorkspaces) : [],
                'currentWorkspace' => $request->user() ? $request->user()->currentWorkspace : null,
            ],
            'flash' => fn () => $request->session()->get('flash'),
        ];
    }
}
