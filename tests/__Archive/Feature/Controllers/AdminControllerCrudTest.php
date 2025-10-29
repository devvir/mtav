<?php

use App\Models\Admin;
use App\Models\Project;

describe('Admin CRUD - Index/Show', function () {

});

describe('Admin CRUD - Create/Store (Admin Only)', function () {
    it('allows admins to create other admins', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());

        $response = inertiaGet($admin, route('admins.create'));

        assertInertiaComponent($response, 'Admins/Create');
        assertInertiaHas($response, 'Admins/Create', 'projects');
    });






    it('validates required fields on creation', function () {
        $admin = createAdmin(asUser: true);

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => '', // Invalid
            'email' => 'invalid', // Invalid email
        ]);

        assertInertiaHasError($response, 'firstname');
        assertInertiaHasError($response, 'email');
    });


});

describe('Admin CRUD - Update', function () {
    it('allows admins to update themselves', function () {
        $admin = createAdmin(asUser: true);

        $response = inertiaPatch($admin, route('admins.update', $admin), [
            'firstname' => 'Updated',
            'lastname' => $admin->lastname,
            'email' => $admin->email,
        ]);

        expect($admin->fresh()->firstname)->toBe('Updated');
        $response->assertRedirect();
    });


    it('allows superadmin to update any admin', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $admin = createAdmin(asUser: true);

        $response = inertiaPatch($superadmin, route('admins.update', $admin), [
            'firstname' => 'SuperUpdated',
            'lastname' => $admin->lastname,
            'email' => $admin->email,
        ]);

        expect($admin->fresh()->firstname)->toBe('SuperUpdated');
    });



});

describe('Admin CRUD - Delete', function () {
    it('denies admins from deleting themselves', function () {
        $admin = createAdmin(asUser: true);

        $response = inertiaDelete($admin, route('admins.destroy', $admin));

        // Cannot delete self
        $response->assertForbidden();
        expect(Admin::find($admin->id))->not->toBeNull();
    });

    it('denies admins from deleting other admins', function () {
        $admin1 = createAdmin(asUser: true);
        $admin2 = createAdmin(asUser: true);

        $response = inertiaDelete($admin1, route('admins.destroy', $admin2));

        $response->assertForbidden();
        expect(Admin::find($admin2->id))->not->toBeNull();
    });

    it('allows superadmin to delete admins', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $admin = createAdmin(asUser: true);

        $response = inertiaDelete($superadmin, route('admins.destroy', $admin));

        expect(Admin::find($admin->id))->toBeNull();
        $response->assertRedirect();
    });


});

describe('Admin CRUD - Project Scope Validation', function () {
    it('shows only managed projects in create form for regular admin', function () {
        $admin = createAdmin(asUser: true);
        $managedProject = createProject();
        $managedProject->addAdmin($admin->asAdmin());
        $unmanagedProject = createProject();

        $response = inertiaGet($admin, route('admins.create'));

        $projects = getInertiaProp($response, 'projects');
        $projectIds = collect($projects)->pluck('id')->toArray();

        expect($projectIds)->toContain($managedProject->id)
            ->not->toContain($unmanagedProject->id);
    });

    it('shows all projects for superadmin in create form', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $project1 = createProject();
        $project2 = createProject();

        $response = inertiaGet($superadmin, route('admins.create'));

        $projects = getInertiaProp($response, 'projects');
        expect($projects)->toHaveCount(2);
    });

});
