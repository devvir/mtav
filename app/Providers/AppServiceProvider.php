<?php

namespace App\Providers;

use App\Services\Queue\Workers;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Services\Delivery\GitHub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Abilities superadmins do not get by default: those that need a personal stake in
     * the project (a family, a membership), and deploying (its own list of people).
     */
    private const PERSONAL_ABILITIES = ['rankUnits', 'rsvp', 'deploy'];

    public function boot(): void
    {
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
}
