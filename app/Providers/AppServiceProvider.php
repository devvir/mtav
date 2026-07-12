<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureVite();

        $this->configureModels();

        $this->configureResources();

        $this->configurePolicies();
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
        /**
         * For Dev only:
         *  - preventLazyLoading();
         *  - preventAccessingMissingAttributes();
         *  - preventSilentlyDiscardingAttributes();
         */
        if (! app()->environment('production')) {
            Model::shouldBeStrict();
        }

        /**
         * Automatically eager load relationships when accessed from Collection items.
         */
        Model::automaticallyEagerLoadRelationships();
    }

    protected function configureResources(): void
    {
        /**
         * Disable default data wrapper in JsonResource collections.
         */
        JsonResource::withoutWrapping();
    }

    protected function configurePolicies(): void
    {
        /**
         * Superadmins have full access to any action, bypassing Policies.
         */
        Gate::before(fn (User $user) => $user->isSuperadmin() ?: null);
    }
}
