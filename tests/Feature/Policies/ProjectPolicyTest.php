<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\Project;

describe('Project Policy', function () {
    beforeEach(function () {
        // Prevent accidental superadmin bypass in policy tests
        config(['auth.superadmins' => []]);
    });

    it('allows superadmins to view all projects', function () {
        config(['auth.superadmins' => [1]]);
        $superadmin = Admin::factory()->create(['id' => 1]);

        expect($superadmin->can('viewAny', Project::class))->toBeTrue();
    });

    it('allows admins with multiple projects to view any', function () {
        $admin = Admin::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $project1->addAdmin($admin);
        $project2->addAdmin($admin);

        expect($admin->fresh()->can('viewAny', Project::class))->toBeTrue();
    });

    it('denies admins with single project to view any', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);

        expect($admin->fresh()->can('viewAny', Project::class))->toBeFalse();
    });

    it('allows admin to view projects they manage', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);

        expect($admin->fresh()->can('view', $project))->toBeTrue();
    });

    it('denies admin to view projects they do not manage', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();

        expect($admin->can('view', $project))->toBeFalse();
    });

    it('allows admin to update projects they manage', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);

        expect($admin->fresh()->can('update', $project))->toBeTrue();
    });

    it('allows admin to delete projects they manage', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);

        expect($admin->fresh()->can('delete', $project))->toBeTrue();
    });

    it('allows superadmin to do anything with projects', function () {
        config(['auth.superadmins' => [1]]);
        $superadmin = Admin::factory()->create(['id' => 1]);
        $project = Project::factory()->create();

        expect($superadmin->can('view', $project))->toBeTrue()
            ->and($superadmin->can('update', $project))->toBeTrue()
            ->and($superadmin->can('delete', $project))->toBeTrue();
    });
});

describe('Project Policy - TODO', function () {
    test('only superadmins can create projects', function () {
        // TODO: Verify that create policy exists and only allows superadmins
    })->todo();

    test('members cannot perform any project operations', function () {
        // TODO: Verify all policy methods deny members
    })->todo();
});
