<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Abilities that need a personal stake in the project (a family, a membership),
     * which superadmins do not get by default.
     */
    private const PERSONAL_ABILITIES = ['rankUnits', 'rsvp'];

    public function boot(): void
    {
        $this->configureVite();
        $this->configureModels();

        JsonResource::withoutWrapping();

        Gate::before(fn (User $user, string $ability) => $user->isSuperadmin()
            && ! in_array($ability, self::PERSONAL_ABILITIES) ?: null);
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
