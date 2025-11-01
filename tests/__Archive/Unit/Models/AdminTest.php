<?php

use App\Models\Admin;
use App\Models\Project;
use App\Models\User;

describe('Admin Model', function () {
    it('can determine if they manage a project', function () {
        $admin = Admin::find(11); // Admin #11 manages Project #1
        $project = Project::find(1);

        expect($admin->manages($project))->toBeTrue();
    });

    it('does not manage projects they are not assigned to', function () {
        // Ensure the admin is not a superadmin by setting an empty superadmin list
        config(['auth.superadmins' => []]);

        $admin = Admin::find(11); // Admin #11 manages only Project #1
        $project = Project::find(2); // Project #2

        expect($admin->manages($project))->toBeFalse();
    });

    it('superadmins manage all projects regardless of assignment', function () {
        config(['auth.superadmins' => ['superadmin1@example.com']]);
        $superadmin = Admin::find(1); // Superadmin #1 from universe
        $project = Project::find(2);

        expect($superadmin->manages($project))->toBeTrue();
    });

    it('has projects relationship', function () {
        $admin = Admin::find(12); // Admin #12 manages Projects #2, #3

        expect($admin->projects)->toHaveCount(2);
    });
});

describe('Admin Business Logic - TODO', function () {


});
