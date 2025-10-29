<?php

use App\Models\Family;
use App\Models\Project;

describe('Family CRUD - Index/Show', function () {

    it('allows anyone to view family details', function () {
        $member = createMember(asUser: true);
        $family = createFamily();

        $response = inertiaGet($member, route('families.show', $family));

        assertInertiaComponent($response, 'Families/Show');
    });

});

describe('Family CRUD - Create/Store (Admin Only)', function () {
    it('allows admins to create families', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());

        $response = inertiaGet($admin, route('families.create'));

        assertInertiaComponent($response, 'Families/Create');
        assertInertiaHas($response, 'Families/Create', 'projects');
    });






    it('validates required fields on creation', function () {
        $admin = createAdmin(asUser: true);

        $response = inertiaPost($admin, route('families.store'), [
            'name' => '', // Invalid
        ]);

        assertInertiaHasError($response, 'name');
    });

    it('validates project exists on creation', function () {
        $admin = createAdmin(asUser: true);

        $response = inertiaPost($admin, route('families.store'), [
            'name' => 'Test Family',
            'project' => 99999, // Non-existent
        ]);

        assertInertiaHasError($response, 'project_id');
    });
});

describe('Family CRUD - Update', function () {
    it('allows admin to update any family', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
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
        $member = createMember(asUser: true);
        $project->addMember($member->asMember(), $family);

        $response = inertiaPatch($member, route('families.update', $family), [
            'name' => 'Updated by Member',
        ]);

        expect($family->fresh()->name)->toBe('Updated by Member');
    });

    it('denies member from updating other families', function () {
        $project = createProject();
        $family1 = createFamilyInProject($project);
        $family2 = createFamilyInProject($project);
        $member = createMember(asUser: true);
        $project->addMember($member->asMember(), $family1);

        $response = inertiaPatch($member, route('families.update', $family2), [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
        expect($family2->fresh()->name)->not->toBe('Hacked');
    });


    it('allows superadmin to update any family', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $family = createFamily();

        $response = inertiaPatch($superadmin, route('families.update', $family), [
            'name' => 'SuperAdmin Updated',
        ]);

        expect($family->fresh()->name)->toBe('SuperAdmin Updated');
    });
});

describe('Family CRUD - Delete (Admin Only)', function () {
    it('allows admin to delete families', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
        $family = createFamilyInProject($project);

        $response = inertiaDelete($admin, route('families.destroy', $family));

        expect(Family::find($family->id))->toBeNull();
        $response->assertRedirect(route('families.index'));
    });



    it('allows superadmin to delete any family', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $family = createFamily();

        $response = inertiaDelete($superadmin, route('families.destroy', $family));

        expect(Family::find($family->id))->toBeNull();
    });
});

describe('Family CRUD - Project Scope Validation', function () {
    it('shows only projects admin manages in create form', function () {
        $admin = createAdmin(asUser: true);
        $managedProject = createProject();
        $managedProject->addAdmin($admin->asAdmin());
        $unmanagedProject = createProject();

        $response = inertiaGet($admin, route('families.create'));

        $projects = getInertiaProp($response, 'projects');
        $projectIds = collect($projects)->pluck('id')->toArray();

        expect($projectIds)->toContain($managedProject->id)
            ->not->toContain($unmanagedProject->id);
    });

    it('shows all projects for superadmin in create form', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $project1 = createProject();
        $project2 = createProject();

        $response = inertiaGet($superadmin, route('families.create'));

        $projects = getInertiaProp($response, 'projects');
        expect($projects)->toHaveCount(2);
    });
});
