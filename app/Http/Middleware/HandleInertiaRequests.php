<?php

namespace App\Http\Middleware;

use App\Models\Media;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'locale'        => app()->getLocale(),
            'auth'          => ['user' => $user ? $this->user($user) : null],
            'projects'      => fn () => $user ? $this->projects($user) : [],
            'project'       => fn () => ($project = currentProject()) && $user ? $this->project($project, $user) : null,
            'notifications' => fn () => $user ? ['unread' => Notification::for($user)->unreadBy($user)->count()] : null,
            'flash'         => [
                'success' => $request->session()->get('success'),
                'info'    => $request->session()->get('info'),
                'warning' => $request->session()->get('warning'),
                'error'   => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [...(new Ziggy())->toArray(), 'location' => $request->url()],
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
        return $user->projects()->orderBy('name')->get(['projects.id', 'projects.name'])
            ->map(fn (Project $project) => [
                'id'       => $project->id,
                'name'     => $project->name,
                'is_admin' => (bool) $project->pivot->is_admin,
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
}
