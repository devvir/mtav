<?php

namespace App\Console\Commands;

use App\Models\JoinRequest;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * A superadmin takes no place in a project: an account still in one, or asking to join one,
 * leaves first (or a support account is invited instead, on an address of its own).
 */
class GrantSuperadmin extends Command
{
    protected $signature = 'mtav:superadmin {email} {--revoke : Take the role away instead}';

    protected $description = 'Make an existing account a platform superadmin (or revoke it)';

    public function handle(AccountService $accounts): int
    {
        $user = User::firstWhere('email', strtolower($this->argument('email')));

        if (! $user) {
            $this->error('No account with that email.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            DB::transaction(function () use ($user, $accounts) {
                $user->forceFill(['is_superadmin' => false])->save();
                $accounts->voidSupportInvitations($user);
            });
            $this->info("{$user->email} is no longer a superadmin.");

            return self::SUCCESS;
        }

        $projects = $user->memberships()->count();
        $asking = JoinRequest::pending()->where('user_id', $user->id)->count();

        if ($projects || $asking) {
            $this->error(
                "{$user->email} takes part in {$projects} project(s) and asks to join {$asking}: a superadmin takes no place in any. "
                . 'They leave those projects and withdraw their requests first, or a support account is invited instead, '
                . 'on an address of its own (the console, Superadmins).',
            );

            return self::FAILURE;
        }

        $user->forceFill(['is_superadmin' => true])->save();
        $this->info("{$user->email} is now a superadmin.");

        return self::SUCCESS;
    }
}
