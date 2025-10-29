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
});

describe('Admin CRUD - Update', function () {
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
});
