<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        return [
            ...parent::share($request),
            ...$this->transient($request),

            'name' => config('app.name'),

            'auth' => $this->auth($request),

            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    /**
     * Auth-specific shared data.
     */
    protected function auth(Request $request): ?array
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return compact('user');
        }

        // Convert to the concrete logged-in type of User: Member or Admin
        $user = $user->isMember() ? $user->asMember() : $user->asAdmin();

        return ['user' => [
            ...$user->toResource()->withoutAbilities()->resolve(),
            'can' => $this->policies(),
        ]];
    }

    /**
     * Transient shared data (state, flash messages).
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

    protected function policies(): array
    {
        return [
            'create' => [
                'projects' => Gate::allows('create', Project::class),
                'units' => Gate::allows('create', Unit::class),
                'admins' => Gate::allows('create', Admin::class),
                'families' => Gate::allows('create', Family::class),
                'members' => Gate::allows('create', Member::class),
            ],
            'viewAny' => [
                'projects' => Gate::allows('viewAny', Project::class),
                'units' => Gate::allows('viewAny', Unit::class),
                'admins' => Gate::allows('viewAny', Admin::class),
                'families' => Gate::allows('viewAny', Family::class),
                'members' => Gate::allows('viewAny', Member::class),
            ],
        ];
    }
}
