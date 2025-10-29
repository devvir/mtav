<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;

describe('Project Scope - Admin Restrictions', function () {







});

describe('Project Scope - Member Restrictions', function () {



});

describe('Project Scope - Superadmin Override', function () {
    test('superadmin can view all projects', function () {
        config(['auth.superadmins' => ['a@x.com']]);

        $superadmin = Admin::factory()->create(['email' => 'a@x.com']);
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        // TODO: Superadmin should see all projects regardless of assignment
        expect($superadmin->can('view', $project1))->toBeTrue()
            ->and($superadmin->can('view', $project2))->toBeTrue();
    });

});
