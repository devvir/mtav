<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantSuperadmin extends Command
{
    protected $signature = 'mtav:superadmin {email} {--revoke : Take the role away instead}';

    protected $description = 'Make an existing account a platform superadmin (or revoke it)';

    public function handle(): int
    {
        $user = User::firstWhere('email', strtolower($this->argument('email')));

        if (! $user) {
            $this->error('No account with that email.');

            return self::FAILURE;
        }

        $user->forceFill(['is_superadmin' => ! $this->option('revoke')])->save();

        $this->info($this->option('revoke') ? "{$user->email} is no longer a superadmin." : "{$user->email} is now a superadmin.");

        return self::SUCCESS;
    }
}
