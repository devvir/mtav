<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\User;

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
        $user = User::factory()->create();
        $project1 = \App\Models\Project::factory()->create();
        $project2 = \App\Models\Project::factory()->create();
        $project3 = \App\Models\Project::factory()->create();

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
});
