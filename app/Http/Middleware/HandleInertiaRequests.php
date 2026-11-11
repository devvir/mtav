<?php

namespace App\Http\Middleware;

use App\Http\Props\AccountProps;
use App\Http\Props\ChatProps;
use App\Http\Props\ProjectProps;
use App\Http\Props\ProjectSwitcherProps;
use App\Http\Props\RouteListProps;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Support\Variant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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

        $chat = app(ChatProps::class, ['request' => $request]);

        /** Closures resolve at render time: the project is known only after routing */
        return [
            ...parent::share($request),
            ...($background ? ['errors' => Inertia::always((object) [])] : []),
            'name'        => config('app.name'),
            'institution' => config('app.institution') ?: null,
            'locale'      => app()->getLocale(),
            /** The languages to pick from, each named in itself */
            'languages' => Inertia::once(fn () => config('app.locales')),
            'auth'      => ['user' => $user ? app(AccountProps::class)->of($user) : null],
            'projects'  => fn () => $user ? app(ProjectSwitcherProps::class)->of($user, currentProject(), $request->session()->get('last_project')) : [],
            'project'   => fn () => ($project = currentProject()) && $user ? app(ProjectProps::class)->of($project, $user) : null,
            /** The project last browsed: the person's own pages stay in its frame */
            'lastProject' => fn () => $request->session()->get('last_project'),
            /** On the person's own pages, the project whose chat they carry */
            'chatProject' => fn () => $user ? $chat->chatProject($user) : null,
            /** On those pages with no project framing them (and on its own page): theirs with the Assistant */
            'accountChat'   => fn () => $user ? $chat->accountChat($user) : null,
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
            'routes' => Inertia::once(fn () => app(RouteListProps::class)->of((bool) $user?->isSuperadmin()))
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
