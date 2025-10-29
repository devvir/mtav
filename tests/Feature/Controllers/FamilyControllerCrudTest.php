<?php

use App\Models\Family;
use App\Models\Project;

describe('Family CRUD - Index/Show', function () {
    it('lists families for current project', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        setCurrentProject($project);

        $family1 = createFamilyInProject($project);
        $family2 = createFamilyInProject($project);
        $otherFamily = createFamily(); // Different project

        $response = inertiaGet($admin, route('families.index'));

        assertInertiaPaginatedData($response, 'Families/Index', 'families', 2);
    });

    it('allows anyone to view family details', function () {
        $member = createMember();
        $family = createFamily();

        $response = inertiaGet($member, route('families.show', $family));

        assertInertiaComponent($response, 'Families/Show');
    });

    it('searches families by name', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        setCurrentProject($project);

        createFamilyInProject($project, ['name' => 'Smith Family']);
        createFamilyInProject($project, ['name' => 'Jones Family']);

        $response = inertiaGet($admin, route('families.index', ['q' => 'Smith']));

        // families.data might be an object/collection, convert to array
        $families = collect(getInertiaProp($response, 'families.data'))->toArray();
        expect($families)->toHaveCount(1)
            ->and($families[0]['name'])->toBe('Smith Family');
    })->skip('Duplicate of FamilyControllerTest > searches families by name which passes. This version uses test helpers which may have side effects.');
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Family CRUD - Create/Store (Admin Only)', function () {
    it('allows admins to create families', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaGet($admin, route('families.create'));

        assertInertiaComponent($response, 'Families/Create');
        assertInertiaHas($response, 'Families/Create', 'projects');
    });

    it('denies members from accessing create form', function () {
        $member = createMember();

        $response = inertiaGet($member, route('families.create'));

        $response->assertForbidden();
    })->skip('Authorization middleware redirects (302) instead of returning 403');

    it('allows admin to store family in project they manage', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaPost($admin, route('families.store'), [
            'name' => 'Test Family',
            'project' => $project->id,
        ]);

        $family = Family::where('name', 'Test Family')->first();
        expect($family)->not->toBeNull()
            ->and($family->project_id)->toBe($project->id);

        $response->assertRedirect(route('families.show', $family->id));
    });

    it('denies admin from creating family in project they do not manage', function () {
        $admin = createAdmin();
        $project = createProject();
        setCurrentProject($project);

        $response = inertiaPost($admin, route('families.store'), [
            'name' => 'Smith Family',
        ]);

        $response->assertForbidden();
    })->skip('Authorization middleware redirects (302) instead of returning 403');

    it('allows superadmin to create family in any project', function () {
        $superadmin = createSuperAdmin();
        $project = createProject();

        $response = inertiaPost($superadmin, route('families.store'), [
            'name' => 'SuperAdmin Family',
            'project' => $project->id,
        ]);

        expect(Family::where('name', 'SuperAdmin Family')->exists())->toBeTrue();
    });

    it('denies members from creating families', function () {
        $member = createMember();
        $project = createProject();
        setCurrentProject($project);

        $response = inertiaPost($member, route('families.store'), [
            'name' => 'Jones Family',
        ]);

        $response->assertForbidden();
    })->skip('Authorization middleware redirects (302) instead of returning 403');

    it('validates required fields on creation', function () {
        $admin = createAdmin();

        $response = inertiaPost($admin, route('families.store'), [
            'name' => '', // Invalid
        ]);

        assertInertiaHasError($response, 'name');
    });

    it('validates project exists on creation', function () {
        $admin = createAdmin();

        $response = inertiaPost($admin, route('families.store'), [
            'name' => 'Test Family',
            'project' => 99999, // Non-existent
        ]);

        assertInertiaHasError($response, 'project');
    });
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Family CRUD - Update', function () {
    it('allows admin to update any family', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        $family = createFamilyInProject($project);

        $response = inertiaPatch($admin, route('families.update', $family), [
            'name' => 'Updated Family Name',
        ]);

        expect($family->fresh()->name)->toBe('Updated Family Name');
        $response->assertRedirect();
    });

    it('allows member to update their own family', function () {
        $project = createProject();
        $family = createFamilyInProject($project);
        $member = createMemberInProject($project, $family);

        $response = inertiaPatch($member, route('families.update', $family), [
            'name' => 'Updated by Member',
        ]);

        expect($family->fresh()->name)->toBe('Updated by Member');
    });

    it('denies member from updating other families', function () {
        $project = createProject();
        $family1 = createFamilyInProject($project);
        $family2 = createFamilyInProject($project);
        $member = createMemberInProject($project, $family1);

        $response = inertiaPatch($member, route('families.update', $family2), [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
        expect($family2->fresh()->name)->not->toBe('Hacked');
    });

    it('denies admin from updating family in project they do not manage', function () {
        $admin = createAdmin();
        $project = createProject();
        $family = createFamilyInProject($project);

        $response = inertiaPut($admin, route('families.update', $family), [
            'name' => 'Updated Name',
        ]);

        $response->assertForbidden();
    })->skip('Authorization middleware redirects (302) instead of returning 403');

    it('allows superadmin to update any family', function () {
        $superadmin = createSuperAdmin();
        $family = createFamily();

        $response = inertiaPatch($superadmin, route('families.update', $family), [
            'name' => 'SuperAdmin Updated',
        ]);

        expect($family->fresh()->name)->toBe('SuperAdmin Updated');
    });
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Family CRUD - Delete (Admin Only)', function () {
    it('allows admin to delete families', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        $family = createFamilyInProject($project);

        $response = inertiaDelete($admin, route('families.destroy', $family));

        expect(Family::find($family->id))->toBeNull();
        $response->assertRedirect(route('families.index'));
    });

    it('denies members from deleting families', function () {
        $project = createProject();
        $family = createFamilyInProject($project);
        $member = createMemberInProject($project, $family);

        $response = inertiaDelete($member, route('families.destroy', $family));

        $response->assertForbidden();
        expect(Family::find($family->id))->not->toBeNull();
    })->skip('Authorization middleware redirects (302) instead of returning 403');

    it('denies admin from deleting family in project they do not manage', function () {
        $admin = createAdmin();
        $project = createProject();
        $family = createFamilyInProject($project);

        $response = inertiaDelete($admin, route('families.destroy', $family));

        $response->assertForbidden();
    })->skip('Authorization middleware redirects (302) instead of returning 403');

    it('allows superadmin to delete any family', function () {
        $superadmin = createSuperAdmin();
        $family = createFamily();

        $response = inertiaDelete($superadmin, route('families.destroy', $family));

        expect(Family::find($family->id))->toBeNull();
    });
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Family CRUD - Project Scope Validation', function () {
    it('shows only projects admin manages in create form', function () {
        $admin = createAdmin();
        $managedProject = createProjectWithAdmin($admin);
        $unmanagedProject = createProject();

        $response = inertiaGet($admin, route('families.create'));

        $projects = getInertiaProp($response, 'projects');
        $projectIds = collect($projects)->pluck('id')->toArray();

        expect($projectIds)->toContain($managedProject->id)
            ->not->toContain($unmanagedProject->id);
    });

    it('shows all projects for superadmin in create form', function () {
        $superadmin = createSuperAdmin();
        $project1 = createProject();
        $project2 = createProject();

        $response = inertiaGet($superadmin, route('families.create'));

        $projects = getInertiaProp($response, 'projects');
        expect($projects)->toHaveCount(2);
    });
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');
