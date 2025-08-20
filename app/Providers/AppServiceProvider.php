<?php

namespace App\Providers;

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
        Gate::before(fn (User $user) => $user->isSuperAdmin() ?: null);

        JsonResource::withoutWrapping();
    }
}
