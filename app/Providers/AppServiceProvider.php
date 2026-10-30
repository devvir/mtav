<?php

namespace App\Providers;

use App\Services\Queue\Workers;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Services\Delivery\GitHub;
use App\Services\ActivityLog;
use App\Services\Push\PushSender;
use App\Services\Push\WebPushSender;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Abilities superadmins do not get by default: those that need a personal stake in
     * the project (a family, a membership), the chat (only those in a conversation read
     * it), and deploying (its own list of people).
     */
    private const PERSONAL_ABILITIES = ['join', 'askToJoin', 'createOwnFamily', 
        'rankUnits', 'rsvp', 'deploy',
        'startChat', 'readChat', 'writeChat', 'manageGroup', 'leaveGroup', 'unsendMessage',
    ];

    public function register(): void
    {
        $this->app->singleton(PushSender::class, WebPushSender::class);
    }

    public function boot(): void
    {
        $this->recordWaysIn();
        $this->configureVite();
        $this->configureModels();

        JsonResource::withoutWrapping();

        // Workers say they are alive as they loop: what waits on them knows (Workers::running)
        Queue::looping(fn () => Workers::mark());

        Gate::before(fn (User $user, string $ability) => $user->isSuperadmin()
            && ! in_array($ability, self::PERSONAL_ABILITIES) ?: null);

        // Superadmins listed as deployers, where GitHub is set up to deploy
        Gate::define('deploy', fn (User $user) => $user->isSuperadmin()
            && GitHub::configured()
            && in_array(strtolower($user->email), config('delivery.deployers'), true));
    }

    protected function configureVite(): void
    {
        /**
         * Environments that must always serve built assets (testing, staging,
         * prod) set VITE_FORCE_BUILD, which points the hot-file check at a
         * path that never exists. All environments share the repo bind mount,
         * so without this a dev server's public/hot would switch them to HMR.
         */
        if (env('VITE_FORCE_BUILD')) {
            Vite::useHotFile(storage_path('framework/never-hot'));
        }
    }

    protected function configureModels(): void
    {
        Model::shouldBeStrict(! app()->isProduction());
        Model::automaticallyEagerLoadRelationships();
    }

    /** Signing in and out, and failing to, are in the activity log (ActivityLog) */
    private function recordWaysIn(): void
    {
        Event::listen(Login::class, fn (Login $event) => ActivityLog::record('signed_in', actor: $event->user));
        Event::listen(Logout::class, function (Logout $event) {
            $event->user && ActivityLog::record('signed_out', actor: $event->user);
        });
        Event::listen(Failed::class, fn (Failed $event) => ActivityLog::record(
            'sign_in_failed',
            changes: ['email' => $event->credentials['email'] ?? null],
        ));
        Event::listen(PasswordReset::class, fn (PasswordReset $event) => ActivityLog::record('password_reset', actor: $event->user));
    }
}
