<?php

namespace App\Http\Middleware;

use App\Enums\ProjectFeature;
use App\Http\Resources\NotificationResource;
use App\Models\Conversation;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use App\Support\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{

    /** The person's own pages, framed by a project (resources/js/layouts/AccountLayout.tsx) */
    private const FRAMED = ['profile.*', 'notifications.*', 'directory.*', 'documentation.*', 'projects.index', 'projects.create'];
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        // A page loaded in the background is shown to nobody yet: a message or a refusal on its
        // way waits for the visit that shows it, and a form takes the visit without one as success
        $background = $request->header('Purpose') === 'prefetch';
        $background && $request->session()->reflash();

        // Said once: apart from the props, a page the browser brings back does not bring them
        if (! $background && $flashed = array_filter($request->session()->only(['success', 'info', 'warning', 'error', 'confirm']))) {
            Inertia::flash($flashed);
        }

        /** Closures resolve at render time: the project is known only after routing */
        return [
            ...parent::share($request),
            ...($background ? ['errors' => Inertia::always((object) [])] : []),
            'name'        => config('app.name'),
            'institution' => config('app.institution') ?: null,
            'locale'      => app()->getLocale(),
            /** The languages to pick from, each named in itself */
            'languages' => Inertia::once(fn () => config('app.locales')),
            'auth'      => ['user' => $user ? $this->user($user) : null],
            'projects'  => fn () => $user ? $this->projects($user) : [],
            'project'   => fn () => ($project = currentProject()) && $user ? $this->project($project, $user) : null,
            /** The project last browsed: the person's own pages stay in its frame */
            'lastProject' => fn () => $request->session()->get('last_project'),
            /** On those pages, the project whose chat they carry: the same frame's */
            'chatProject' => fn () => $user && $request->routeIs(self::FRAMED) && ($framing = $this->framing($request, $user))
                ? $this->project($framing, $user)
                : null,
            'notifications' => fn () => $user ? ['unread' => Notification::for($user)->unreadBy($user)->count()] : null,
            /** The latest few, for the bell's menu: sent only when it asks (a partial reload) */
            'recentNotifications' => Inertia::optional(fn () => $user
                ? NotificationResource::collection(Notification::for($user)->limit(8)->get())
                : []),
            /**
             * The route list, sent once (the client remembers it, under a name of its own for
             * those who enter the console, so signing in brings theirs), and what changes per
             * visit: the defaults (the project being browsed), where one is and the route's name
             */
            'routes' => Inertia::once(fn () => $this->routes((bool) $user?->isSuperadmin()))
                ->as($user?->isSuperadmin() ? 'routes.console' : 'routes'),
            // What a project may choose of its features here, and what it starts with
            'featureChoices' => Inertia::once(fn () => (object) Variant::choices()),
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

    /**
     * Shared with every page: kept to what the layout reads.
     *
     * @return array<string, mixed>
     */
    /**
     * The routes the browser may call: those of parts of the app this variant has, the
     * platform console's only for those who enter it
     */
    private function routes(bool $console): array
    {
        $routes = (new Ziggy())->toArray();
        $routes['routes'] = array_filter(
            $routes['routes'],
            fn (string $name) => ($console || ! str_starts_with($name, 'admin.'))
                && ($route = Route::getRoutes()->getByName($name)) && Variant::opens($route),
            ARRAY_FILTER_USE_KEY,
        );

        return $routes;
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
            'can_deploy'    => $user->can('deploy'),
            'verified'      => $user->hasVerifiedEmail(),
        ];
    }

    /**
     * The projects the user takes part in (for the project switcher).
     */
    private function projects(User $user): array
    {
        $managed = $user->memberships->where('is_admin', true)->pluck('project_id');

        return $user->reachableProjects()->orderBy('name')->get(['id', 'name', 'organization', 'feature_choices'])
            ->map(fn (Project $project) => [
                'id'           => $project->id,
                'name'         => $project->name,
                'organization' => $project->organization,
                'features'     => $project->features(),
                'is_admin'     => $user->isSuperadmin() || $managed->contains($project->id),
            ])
            ->all();
    }

    /**
     * The project the person's own pages stay in the frame of: the one last browsed, or their
     * only one (the account layout frames them the same way).
     */
    private function framing(Request $request, User $user): ?Project
    {
        $projects = $user->reachableProjects();

        return (clone $projects)->find($request->session()->get('last_project'))
            ?? ((clone $projects)->count() === 1 ? $projects->first() : null);
    }

    /**
     * The project being browsed, and the user's part in it.
     */
    private function project(Project $project, User $user): array
    {
        $family = $user->familyIn($project);
        $manages = $user->can('update', $project);
        // Without guests, until they have a family, finding it is all there is for them
        $inChat = ! $user->needsFamilyIn($project) && $project->hasFeature(ProjectFeature::CHAT) && ((bool) $user->membershipIn($project) || $user->isSuperadmin());

        return [
            'id'                    => $project->id,
            'name'                  => $project->name,
            'is_approved'           => $project->isApproved(),
            'approval_requested_at' => $project->approval_requested_at,
            'family'                => $family ? ['id' => $family->id, 'name' => $family->name] : null,
            // For its admins: the invitations nobody has answered yet (the settings' tab says so)
            'pending_invitations' => $manages ? $project->invitations()->pending()->count() : null,
            // And those asking to join from the directory
            'join_requests' => $manages ? $project->joinRequests()->pending()->count() : null,
            'is_guest'      => $user->isGuestIn($project),
            'allows_guests' => $project->allows_guests,
            // Let in without guests, until they have a family it is all there is for them
            'needs_family' => $user->needsFamilyIn($project),
            'features'     => $project->features(),
            // The conversations with something new for them (the chat's place says so)
            'unread_messages' => $inChat ? Conversation::of($user, $project)->unreadBy($user)->count() : null,
            // Someone's first arrival: the Assistant's conversation, that opens to greet them
            'welcome' => $inChat && $project->hasFeature(ProjectFeature::ASSISTANT) && ! $user->welcomed_at && config('assistant.driver') !== 'off'
                ? Conversation::of($user, $project)->where('is_assistant', true)->value('id')
                : null,
            'can' => [
                'manage' => $manages,
                'invite' => $user->can('invite', [$project, $family]),
                'rank'   => $user->can('rankUnits', $project),
                'upload' => ($project->hasFeature(ProjectFeature::GALLERY) || $project->hasFeature(ProjectFeature::FILES))
                    && $user->can('create', [Media::class, $project]),
                'approve' => $user->can('approve', $project),
                'chat'    => $inChat,
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
