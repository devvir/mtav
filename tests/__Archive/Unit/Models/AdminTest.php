<?php

use App\Models\Admin;
use App\Models\Project;
use App\Models\User;

describe('Admin Model', function () {
    it('can determine if they manage a project', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();

        $project->addAdmin($admin);

        expect($admin->fresh()->manages($project))->toBeTrue();
    });

    it('does not manage projects they are not assigned to', function () {
        // Ensure the admin is not a superadmin by setting an empty superadmin list
        config(['auth.superadmins' => []]);

        $admin = Admin::factory()->create();
        $project = Project::factory()->create();

        expect($admin->manages($project))->toBeFalse();
    });

    it('superadmins manage all projects regardless of assignment', function () {
        config(['auth.superadmins' => [999]]);
        $superadmin = Admin::factory()->create(['id' => 999]);
        $project = Project::factory()->create();

        expect($superadmin->manages($project))->toBeTrue();
    });

    it('applies admin global scope to exclude members', function () {
        User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        expect(Admin::all())->toHaveCount(1)
            ->and(Admin::first()->id)->toBe($admin->id);
    });

    it('has projects relationship', function () {
        $admin = Admin::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $project1->addAdmin($admin);
        $project2->addAdmin($admin);

        expect($admin->fresh()->projects)->toHaveCount(2);
    });
});

describe('Admin Business Logic - TODO', function () {


});
