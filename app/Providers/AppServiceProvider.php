<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLog;
use App\Services\Assistant\Brain;
use App\Services\Assistant\Brains\ApiBrain;
use App\Services\Assistant\Brains\CliBrain;
use App\Services\Delivery\GitHub;
use App\Services\Push\PushSender;
use App\Services\Push\WebPushSender;
use App\Services\Queue\Workers;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Markdown;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Queue\SteadyWorker;
use Illuminate\Queue\Worker;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Abilities superadmins do not get by default: those that need a personal stake in
     * the project (a family, a membership), the chat (only those in a conversation read
     * it), and deploying (its own list of people).
     */
    private const PERSONAL_ABILITIES = ['join', 'askToJoin', 'createOwnFamily', 'answerRequests',
        'rankUnits', 'rsvp', 'deploy',
        'startChat', 'readChat', 'writeChat', 'manageGroup', 'leaveGroup', 'unsendMessage', 'editMessage',
    ];

    public function register(): void
    {
        $this->app->singleton(PushSender::class, WebPushSender::class);
        $this->app->bind(Brain::class, fn () => config('assistant.driver') === 'cli' ? new CliBrain() : new ApiBrain());
        // The queue worker waits for a database that is not there yet, rather than failing in a loop
        $this->app->extend('queue.worker', fn (Worker $worker) => SteadyWorker::from($worker));
    }

    public function boot(): void
    {
        $this->recordWaysIn();
        $this->configureVite();
        $this->configureModels();

        JsonResource::withoutWrapping();

        // What people wrote (names of projects, families, people) reaches mail as text, never as
        // Markdown: a name like "[Reset your password](https://…)" shows as typed, not as a link
        Markdown::withSecuredEncoding();

        // Production's data is never started over: wiping commands refuse to run there
        DB::prohibitDestructiveCommands(config('app.deployment') === 'production');

        // Workers of the main queue, where the lottery waits, say they are alive as they loop
        // (Workers::running). Answering false here would stop the worker: it answers nothing.
        Queue::looping(function (Looping $worker): void {
            if (in_array('default', explode(',', $worker->queue), true)) {
                Workers::mark();
            }
        });

        Gate::before(fn (User $user, string $ability) => $user->isSuperadmin()
            && ! in_array($ability, self::PERSONAL_ABILITIES) ?: null);

        // Superadmins listed as deployers, where GitHub is set up to deploy
        Gate::define('deploy', fn (User $user) => $user->isSuperadmin()
            && GitHub::configured()
            && in_array(strtolower($user->email), config('delivery.deployers'), true));
    }

    protected function configureVite(): void
    {
        // Testing and e2e share the checkout with dev: its public/hot would switch them to the
        // dev server. Pointed at a hot file that never exists, they serve the built assets.
        if (config('app.built_assets')) {
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
