<?php

use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Member CRUD - Index/Show', function () {
    it('lists members for current project', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
        setCurrentProject($project);

        $family = createFamilyInProject($project);
        $member1 = createMemberInProject($project, $family);
        $member2 = createMemberInProject($project, $family);
        $otherMember = createMember(); // Not in project

        $response = inertiaGet($admin, route('members.index'));

        assertInertiaPaginatedData($response, 'Members/Index', 'members', 2);
    });
});

describe('Member CRUD - Create/Store (Admins and Members)', function () {
    it('allows admins to create members', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
        setCurrentProject($project);

        $response = inertiaGet($admin, route('members.create'));

        assertInertiaComponent($response, 'Members/Create');
        assertInertiaHas($response, 'Members/Create', ['families', 'projects']);
    });
});

describe('Member CRUD - Critical: Family/Project Constraints', function () {



});

describe('Member CRUD - Update', function () {
    it('allows admins to update any member', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
        $member = createMemberInProject($project);

        $response = inertiaPatch($admin, route('members.update', $member), [
            'firstname' => 'Updated',
            'lastname' => $member->lastname,
            'email' => $member->email,
        ]);

        expect($member->fresh()->firstname)->toBe('Updated');
        $response->assertRedirect();
    });




    it('allows superadmin to update any member', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $member = createMember();

        $response = inertiaPatch($superadmin, route('members.update', $member), [
            'firstname' => 'SuperAdmin Updated',
            'lastname' => $member->lastname,
            'email' => $member->email,
        ]);

        expect($member->fresh()->firstname)->toBe('SuperAdmin Updated');
    });

    it('validates email uniqueness on update', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
        $member1 = createMemberInProject($project, null, ['email' => 'first@example.com']);
        $member2 = createMemberInProject($project, null, ['email' => 'second@example.com']);

        $response = inertiaPatch($admin, route('members.update', $member1), [
            'firstname' => $member1->firstname,
            'lastname' => $member1->lastname,
            'email' => 'second@example.com', // Already taken
        ]);

        assertInertiaHasError($response, 'email');
    });

});

describe('Member CRUD - Delete', function () {
    it('allows admins to delete members', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());
        $member = createMemberInProject($project);

        $response = inertiaDelete($admin, route('members.destroy', $member));

        expect(Member::find($member->id))->toBeNull();
        $response->assertRedirect(route('members.index'));
    });




    it('allows superadmin to delete any member', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $member = createMember();

        $response = inertiaDelete($superadmin, route('members.destroy', $member));

        expect(Member::find($member->id))->toBeNull();
    });
});

describe('Member CRUD - Project Scope Validation', function () {
    it('shows only families from managed projects in create form', function () {
        $admin = createAdmin(asUser: true);
        $managedProject = createProject();
        $managedProject->addAdmin($admin->asAdmin());
        $unmanagedProject = createProject();
        $managedFamily = createFamilyInProject($managedProject);
        $unmanagedFamily = createFamilyInProject($unmanagedProject);

        session(['project' => $managedProject]);
        $response = inertiaGet($admin, route('members.create'));

        $families = getInertiaProp($response, 'families');
        $familyIds = collect($families)->pluck('id')->toArray();

        expect($familyIds)->toContain($managedFamily->id)
            ->not->toContain($unmanagedFamily->id);
    });

    it('shows only managed projects in create form for admins', function () {
        $admin = createAdmin(asUser: true);
        $managedProject = createProject();
        $managedProject->addAdmin($admin->asAdmin());
        $unmanagedProject = createProject();

        $response = inertiaGet($admin, route('members.create'));

        $projects = getInertiaProp($response, 'projects');
        $projectIds = collect($projects)->pluck('id')->toArray();

        expect($projectIds)->toContain($managedProject->id)
            ->not->toContain($unmanagedProject->id);
    });
});
