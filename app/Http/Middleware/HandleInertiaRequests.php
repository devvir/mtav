<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            ...$this->transient($request),

            'name' => config('app.name'),

            'auth' => [
                'user' => $user?->toResource()->withoutAbilities()->resolve(),
                'verified' => $user ? (bool) $user->email_verified_at : null,
            ],

            'ziggy' => [
                ...(new Ziggy())->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    /**
     * Transient shared data specific to the current application.
     */
    protected function transient(Request $request): array
    {
        return [
            'state' => [
                'route' => $request->route()?->getName(),
                'project' => Project::current(),
                'groupMembers' => state('groupMembers'),
            ],

            'flash' => [
                'success' => $request->session()->get('success'),
            ],

            'sidebarOpen' => $request->cookie('sidebar_state', 'true') === 'true',
        ];
    }
}
