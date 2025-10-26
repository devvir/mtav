<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;

describe('Project Scope - Admin Restrictions', function () {
    test('admin can only view families in their managed projects', function () {
        // TODO: GET /families should filter by admin's projects
    })->todo();

    test('admin can only view members in their managed projects', function () {
        // TODO: GET /members should filter by admin's projects
    })->todo();

    test('admin can only view units in their managed projects', function () {
        // TODO: GET /units should filter by admin's projects
    })->todo();

    test('admin cannot create family in unmanaged project', function () {
        // TODO: POST /families with project_id they don't manage
        // should return 403
    })->todo();

    test('admin cannot create member in unmanaged project', function () {
        // TODO: POST /members with project_id they don't manage
        // should return 403
    })->todo();

    test('admin cannot create unit in unmanaged project', function () {
        // TODO: POST /units should validate project ownership
    })->todo();

    test('admin cannot update family from unmanaged project', function () {
        // TODO: PATCH /families/{id} where family.project_id
        // is not managed by admin should return 403
    })->todo();

    test('admin cannot delete resources from unmanaged projects', function () {
        // TODO: DELETE should check project ownership
    })->todo();
});

describe('Project Scope - Member Restrictions', function () {
    test('member can only view data from their active project', function () {
        // TODO: Member should only see:
        // - Families from their project
        // - Members from their project
        // - Units from their project
    })->todo();

    test('member cannot view data from other projects', function () {
        // TODO: Direct access to resources from other projects
        // should return 403 or 404
    })->todo();

    test('member can only create members in their own project', function () {
        // TODO: POST /members should auto-set project to member's project
        // and validate family is in that project
    })->todo();

    test('member cannot switch to different project', function () {
        // TODO: Member shouldn't have ability to change their active project
        // Only admin can do that (by moving the family)
    })->todo();
});

describe('Project Scope - Superadmin Override', function () {
    test('superadmin can view all projects', function () {
        config(['auth.superadmins' => [1]]);
        $superadmin = Admin::factory()->create(['id' => 1]);
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        // TODO: Superadmin should see all projects regardless of assignment
        expect($superadmin->can('view', $project1))->toBeTrue()
            ->and($superadmin->can('view', $project2))->toBeTrue();
    });

    test('superadmin bypasses all project scope restrictions', function () {
        // TODO: Superadmin should be able to create/update/delete
        // resources across all projects
    })->todo();
});
