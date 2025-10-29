<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Superadmins have full access to any action
        Gate::before(
            fn (User $user) => $user->isSuperadmin() ?: null
        );

        // Uncomment to allow undefined policies by default
        // Gate::after(fn (?User $user) => true);

        JsonResource::withoutWrapping();

        if (! app()->environment('production')) {
            Model::shouldBeStrict();
        }
    }
}
