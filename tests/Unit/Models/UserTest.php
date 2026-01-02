<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('User Model', function () {
    it('can be converted to a Member when is_admin is false', function () {
        $user = User::find(102); // Member #102 from universe

        expect($user->asMember())
            ->toBeInstanceOf(Member::class)
            ->firstname->toBe($user->firstname);
    });

    it('returns null when converting to Member if is_admin is true', function () {
        $user = User::find(11); // Admin #11 from universe

        expect($user->asMember())->toBeNull();
    });

    it('can be converted to an Admin when is_admin is true', function () {
        $user = User::find(11); // Admin #11 from universe

        expect($user->asAdmin())
            ->toBeInstanceOf(Admin::class)
            ->firstname->toBe($user->firstname);
    });

    it('returns null when converting to Admin if is_admin is false', function () {
        $user = User::find(102); // Member #102 from universe

        expect($user->asAdmin())->toBeNull();
    });

    it('identifies members correctly', function () {
        $member = User::find(102); // Member #102 from universe
        $admin = User::find(11); // Admin #11 from universe

        expect($member->isMember())->toBeTrue()
            ->and($member->isAdmin())->toBeFalse()
            ->and($admin->isMember())->toBeFalse()
            ->and($admin->isAdmin())->toBeTrue();
    });

    it('identifies superadmins based on config', function () {
        config(['auth.superadmins' => ['superadmin1@example.com']]);

        $superadmin = User::find(1); // Superadmin #1 from universe
        $regularAdmin = User::find(11); // Admin #11 from universe
        $member = User::find(102); // Member #102 from universe

        expect($superadmin->isSuperadmin())->toBeTrue()
            ->and($regularAdmin->isSuperadmin())->toBeFalse()
            ->and($member->isSuperadmin())->toBeFalse();
    });

    it('has projects relationship that returns only active projects', function () {
        // Factory: Need fresh user and projects with controlled active/inactive state
        // Fixture projects have complex assignments; this tests the active filtering logic
        $user = User::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $project3 = Project::factory()->create();

        // Attach with active=true
        $user->projects()->attach($project1, ['active' => true]);
        $user->projects()->attach($project2, ['active' => true]);
        // Attach with active=false
        $user->projects()->attach($project3, ['active' => false]);

        expect($user->projects)->toHaveCount(2)
            ->and($user->projects->contains($project1))->toBeTrue()
            ->and($user->projects->contains($project2))->toBeTrue()
            ->and($user->projects->contains($project3))->toBeFalse();
    });

    it('has many media through media relation', function () {
        $user = User::find(102);

        expect($user->media())->toBeInstanceOf(HasMany::class);
    });

    it('has notifications through HasNotifications trait', function () {
        $user = User::find(102);

        $notifications = $user->notifications()->get();

        expect($notifications)->toBeInstanceOf(Collection::class)
            ->and($notifications->count())->toBe(7); // User 102 has 2 private + 5 from project #1
    });

    it('has privateNotifications through HasNotifications trait', function () {
        $user = User::find(102);

        $privateNotifications = $user->privateNotifications()->get();

        expect($privateNotifications)->toBeInstanceOf(Collection::class)
            ->and($privateNotifications->count())->toBe(2); // User 102 has 2 private notifications
    });

    it('has read notifications for authenticated user', function () {
        $user = User::find(102);
        $notification = $user->notifications()->first();

        // Mark one notification as read
        $notification->markAsReadBy($user);

        expect($user->readNotifications()->count())->toBe(1);
    });

    it('has unread notifications for authenticated user', function () {
        $user = User::find(102);

        // All notifications should be unread initially
        expect($user->unreadNotifications()->count())->toBe(7); // 2 private + 5 project
    });

    it('has alphabetically scope', function () {
        $users = User::alphabetically()->get();

        // Verify users are sorted by firstname then lastname (case-insensitive)
        $firstnames = $users->pluck('firstname')->toArray();
        $sortedFirstnames = $firstnames;
        natcasesort($sortedFirstnames);
        $sortedFirstnames = array_values($sortedFirstnames); // Re-index after sort

        expect($firstnames)->toBe($sortedFirstnames);
    });

    it('has fullname attribute combining firstname and lastname', function () {
        $user = User::find(102);

        expect($user->fullname)->toBe($user->firstname . ' ' . $user->lastname);
    });

    it('lowercases email on set', function () {
        $user = User::factory()->create(['email' => 'TEST@EXAMPLE.COM']);

        expect($user->email)->toBe('test@example.com');
    });

    it('identifies invited users correctly', function () {
        $invited = User::find(148); // Invited user
        $registered = User::find(102); // Completed registration

        expect($invited->isInvited())->toBeTrue()
            ->and($invited->completedRegistration())->toBeFalse()
            ->and($registered->isInvited())->toBeFalse()
            ->and($registered->completedRegistration())->toBeTrue();
    });
});
