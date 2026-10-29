<?php

namespace App\Http\Middleware;

use App\Models\Media;
use App\Models\Notification;
use App\Models\Project;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Shared with every page: kept to what the layout reads.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        /** Closures resolve at render time: the project is known only after routing */
        return [
            ...parent::share($request),
            'name'          => config('app.name'),
            'institution'   => config('app.institution') ?: null,
            'locale'        => app()->getLocale(),
            'auth'          => ['user' => $user ? $this->user($user) : null],
            'projects'      => fn () => $user ? $this->projects($user) : [],
            'project'       => fn () => ($project = currentProject()) && $user ? $this->project($project, $user) : null,
            'notifications' => fn () => $user ? ['unread' => Notification::for($user)->unreadBy($user)->count()] : null,
            /** The latest few, for the bell's menu: sent only when it asks (a partial reload) */
            'recentNotifications' => Inertia::optional(fn () => $user
                ? NotificationResource::collection(Notification::for($user)->limit(8)->get())
                : []),
            'flash' => [
                'success' => $request->session()->get('success'),
                'info'    => $request->session()->get('info'),
                'warning' => $request->session()->get('warning'),
                'error'   => $request->session()->get('error'),
            ],
            /**
             * The route list, sent once (the client remembers it), and what changes per visit:
             * the defaults (the project being browsed), where one is and the route's name
             */
            'routes' => Inertia::once(fn () => (new Ziggy())->toArray()),
            /** Where browsers reach the websocket server (null: no live updates) */
            'realtime' => Inertia::once(fn () => $this->realtime()),
            'ziggy'    => fn () => [
                'defaults' => url()->getDefaultParameters(),
                'name'     => $request->route()?->getName(),
                'location' => [
                    'host'     => $request->getHttpHost(),
                    'pathname' => '/' . ltrim($request->path(), '/'),
                    'search'   => $request->getQueryString() ? '?' . $request->getQueryString() : '',
                ],
            ],
        ];
    }

    private function user(User $user): array
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'firstname'     => $user->firstname,
            'lastname'      => $user->lastname,
            'email'         => $user->email,
            'avatar'        => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
            'is_superadmin' => $user->isSuperadmin(),
            'verified'      => $user->hasVerifiedEmail(),
        ];
    }

    /**
     * The projects the user takes part in (for the project switcher).
     */
    private function projects(User $user): array
    {
        $managed = $user->memberships->where('is_admin', true)->pluck('project_id');

        return $user->reachableProjects()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Project $project) => [
                'id'       => $project->id,
                'name'     => $project->name,
                'is_admin' => $user->isSuperadmin() || $managed->contains($project->id),
            ])
            ->all();
    }

    /**
     * The project being browsed, and the user's part in it.
     */
    private function project(Project $project, User $user): array
    {
        $family = $user->familyIn($project);

        return [
            'id'                    => $project->id,
            'name'                  => $project->name,
            'is_approved'           => $project->isApproved(),
            'approval_requested_at' => $project->approval_requested_at,
            'family'                => $family ? ['id' => $family->id, 'name' => $family->name] : null,
            'can'                   => [
                'manage'  => $user->can('update', $project),
                'invite'  => $user->can('invite', [$project, $family]),
                'rank'    => $user->can('rankUnits', $project),
                'upload'  => $user->can('create', [Media::class, $project]),
                'approve' => $user->can('approve', $project),
            ],
        ];
    }

    /**
     * The websocket server as browsers reach it, known at run time: one build of the app
     * serves every environment.
     *
     * @return array{key: string, host: string, port: int, scheme: string}|null
     */
    private function realtime(): ?array
    {
        $reverb = config('broadcasting.connections.reverb');

        if (config('broadcasting.default') !== 'reverb' || ! $reverb['key']) {
            return null;
        }

        return ['key' => $reverb['key'], ...$reverb['browser']];
    }
}
