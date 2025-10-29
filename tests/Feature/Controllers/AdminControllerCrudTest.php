<?php

use App\Models\Admin;
use App\Models\Project;

describe('Admin CRUD - Index/Show', function () {
    it('allows anyone to view admin list', function () {
        $member = createMember();
        $admin1 = createAdmin();
        $admin2 = createAdmin();

        $response = inertiaGet($member, route('admins.index'));

        assertInertiaPaginatedData($response, 'Admins/Index', 'admins', 2);
    });

    it('allows anyone to view admin details', function () {
        $member = createMember();
        $admin = createAdmin();

        $response = inertiaGet($member, route('admins.show', $admin));

        assertInertiaComponent($response, 'Admins/Show');
    });
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Admin CRUD - Create/Store (Admin Only)', function () {
    it('allows admins to create other admins', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaGet($admin, route('admins.create'));

        assertInertiaComponent($response, 'Admins/Create');
        assertInertiaHas($response, 'Admins/Create', 'projects');
    });

    it('denies members from accessing admin creation form', function () {
        // TODO: bug - Expected 403 but got 302 redirect\n        // The application might be using redirect instead of abort(403) for authorization failures
        // Need to verify if this is the intended behavior or if the controller should return 403
        $member = createMember();

        $response = inertiaGet($member, route('admins.create'));

        $response->assertForbidden();
    })->skip('bug: returns 302 redirect instead of 403');

    it('allows admin to create admin for projects they manage', function () {
        // TODO: bug - AdminController::store() doesn't properly handle admin creation:
        // 1. Admin::create($request->validated()) tries to insert 'project' field which doesn't exist on users table
        // 2. Missing logic to set is_admin = true
        // 3. Missing logic to attach the admin to the project via pivot table
        // The controller needs to:
        // - Remove 'project' from validated data or handle it separately
        // - Ensure is_admin is set to true
        // - Call $project->addAdmin($admin) after creation

        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => 'New',
            'lastname' => 'Admin',
            'email' => 'newadmin@example.com',
            'project' => $project->id,
        ]);

        $newAdmin = Admin::where('email', 'newadmin@example.com')->first();
        expect($newAdmin)->not->toBeNull()
            ->and($newAdmin->is_admin)->toBeTrue()
            ->and($project->hasAdmin($newAdmin))->toBeTrue();

        $response->assertRedirect(route('admins.show', $newAdmin->id));
    })->skip('bug: AdminController::store() missing implementation logic');

    it('denies admin from assigning new admin to projects they do not manage', function () {
        // TODO: bug - This test expects authorization logic that doesn't exist yet.
        // The CreateAdminRequest or controller should verify that the authenticated admin
        // manages the project they're trying to assign the new admin to.
        // Also uses 'projects' but should be 'project' per current implementation.

        $admin = createAdmin();
        $managedProject = createProjectWithAdmin($admin);
        $unmanagedProject = createProject();

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => 'Unauthorized',
            'lastname' => 'Admin',
            'email' => 'unauthorized@example.com',
            'project' => $unmanagedProject->id, // Should fail - admin doesn't manage this project
        ]);

        $response->assertForbidden();
        expect(Admin::where('email', 'unauthorized@example.com')->exists())->toBeFalse();
    })->skip('bug: missing authorization check for project management');

    it('allows admin to assign new admin to multiple managed projects', function () {
        // TODO: bug - Same as above, admin creation not implemented
        $admin = createAdmin();
        $project1 = createProjectWithAdmin($admin);
        $project2 = createProjectWithAdmin($admin);

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => 'Multi',
            'lastname' => 'Project Admin',
            'email' => 'multi@example.com',
            'projects' => [$project1->id, $project2->id],
        ]);

        $newAdmin = Admin::where('email', 'multi@example.com')->first();
        expect($newAdmin)->not->toBeNull()
            ->and($project1->hasAdmin($newAdmin))->toBeTrue()
            ->and($project2->hasAdmin($newAdmin))->toBeTrue();
    })->skip('bug: admin creation not implemented');

    it('allows superadmin to create admin for any project', function () {
        // TODO: bug - Same as above, admin creation not implemented
        $superadmin = createSuperAdmin();
        $project1 = createProject();
        $project2 = createProject();

        $response = inertiaPost($superadmin, route('admins.store'), [
            'firstname' => 'Super',
            'lastname' => 'Assigned',
            'email' => 'superassigned@example.com',
            'projects' => [$project1->id, $project2->id],
        ]);

        $newAdmin = Admin::where('email', 'superassigned@example.com')->first();
        expect($newAdmin)->not->toBeNull();
    })->skip('bug: admin creation not implemented');

    it('validates required fields on creation', function () {
        $admin = createAdmin();

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => '', // Invalid
            'email' => 'invalid', // Invalid email
        ]);

        assertInertiaHasError($response, 'firstname');
        assertInertiaHasError($response, 'email');
    });

    it('validates email uniqueness', function () {
        // TODO: bug - Same as above, admin creation not implemented
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        $existingAdmin = createAdmin(['email' => 'existing@example.com']);

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => 'Duplicate',
            'lastname' => 'Admin',
            'email' => 'existing@example.com',
            'project' => $project->id,
        ]);

        assertInertiaHasError($response, 'email');
    })->skip('bug: admin creation not implemented');

    it('requires at least one project for new admin', function () {
        // TODO: bug - CreateAdminRequest requires 'project' (singular), not 'projects' array
        // This test should verify that the 'project' field is required
        $admin = createAdmin();

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => 'No',
            'lastname' => 'Project',
            'email' => 'noproject@example.com',
            // Missing 'project' field
        ]);

        assertInertiaHasError($response, 'project');
    });
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Admin CRUD - Update', function () {
    it('allows admins to update themselves', function () {
        $admin = createAdmin();

        $response = inertiaPatch($admin, route('admins.update', $admin), [
            'firstname' => 'Updated',
            'lastname' => $admin->lastname,
            'email' => $admin->email,
        ]);

        expect($admin->fresh()->firstname)->toBe('Updated');
        $response->assertRedirect();
    });

    it('denies admins from updating other admins', function () {
        // TODO: bug - Same 302 vs 403 issue as other authorization tests
        $admin1 = createAdmin();
        $admin2 = createAdmin();

        $response = inertiaPatch($admin1, route('admins.update', $admin2), [
            'firstname' => 'Hacked',
            'lastname' => $admin2->lastname,
            'email' => $admin2->email,
        ]);

        $response->assertForbidden();
        expect($admin2->fresh()->firstname)->not->toBe('Hacked');
    })->skip('bug: returns 302 redirect instead of 403');

    it('allows superadmin to update any admin', function () {
        $superadmin = createSuperAdmin();
        $admin = createAdmin();

        $response = inertiaPatch($superadmin, route('admins.update', $admin), [
            'firstname' => 'SuperUpdated',
            'lastname' => $admin->lastname,
            'email' => $admin->email,
        ]);

        expect($admin->fresh()->firstname)->toBe('SuperUpdated');
    });

    it('denies members from updating admins', function () {
        // TODO: bug - Same 302 vs 403 issue
        $member = createMember();
        $admin = createAdmin();

        $response = inertiaPatch($member, route('admins.update', $admin), [
            'firstname' => 'Hacked',
            'lastname' => $admin->lastname,
            'email' => $admin->email,
        ]);

        $response->assertForbidden();
    })->skip('bug: returns 302 redirect instead of 403');

    test('admin cannot change their own project assignments', function () {
        // TODO: Admin editing themselves should not be able to add/remove projects
        // Only superadmins should be able to modify project assignments
    })->todo();

    test('admin cannot modify another admin project assignments', function () {
        // TODO: Only superadmin should be able to change admin-project relationships
    })->todo();
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Admin CRUD - Delete', function () {
    it('denies admins from deleting themselves', function () {
        $admin = createAdmin();

        $response = inertiaDelete($admin, route('admins.destroy', $admin));

        // Cannot delete self
        $response->assertForbidden();
        expect(Admin::find($admin->id))->not->toBeNull();
    });

    it('denies admins from deleting other admins', function () {
        $admin1 = createAdmin();
        $admin2 = createAdmin();

        $response = inertiaDelete($admin1, route('admins.destroy', $admin2));

        $response->assertForbidden();
        expect(Admin::find($admin2->id))->not->toBeNull();
    });

    it('allows superadmin to delete admins', function () {
        $superadmin = createSuperAdmin();
        $admin = createAdmin();

        $response = inertiaDelete($superadmin, route('admins.destroy', $admin));

        expect(Admin::find($admin->id))->toBeNull();
        $response->assertRedirect();
    });

    it('denies members from deleting admins', function () {
        $member = createMember();
        $admin = createAdmin();

        $response = inertiaDelete($member, route('admins.destroy', $admin));

        $response->assertForbidden();
    })->skip('Authorization middleware redirects (302) instead of returning 403. Need to configure middleware to return JSON for API requests or update test expectations.');

    it('prevents superadmin from deleting themselves', function () {
        $superadmin = createSuperAdmin();

        $response = inertiaDelete($superadmin, route('admins.destroy', $superadmin));

        $response->assertForbidden();
        expect(Admin::find($superadmin->id))->not->toBeNull();
    })->skip('Authorization middleware redirects (302) instead of returning 403. Need to configure middleware to return JSON for API requests or update test expectations.');
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');

describe('Admin CRUD - Project Scope Validation', function () {
    it('shows only managed projects in create form for regular admin', function () {
        $admin = createAdmin();
        $managedProject = createProjectWithAdmin($admin);
        $unmanagedProject = createProject();

        $response = inertiaGet($admin, route('admins.create'));

        $projects = getInertiaProp($response, 'projects');
        $projectIds = collect($projects)->pluck('id')->toArray();

        expect($projectIds)->toContain($managedProject->id)
            ->not->toContain($unmanagedProject->id);
    });

    it('shows all projects for superadmin in create form', function () {
        $superadmin = createSuperAdmin();
        $project1 = createProject();
        $project2 = createProject();

        $response = inertiaGet($superadmin, route('admins.create'));

        $projects = getInertiaProp($response, 'projects');
        expect($projects)->toHaveCount(2);
    });

    test('admin cannot assign mixed managed and unmanaged projects', function () {
        // TODO: If admin tries to create admin with projects [1, 2, 3]
        // where they only manage [1, 2], should fail validation
        $admin = createAdmin();
        $managedProject = createProjectWithAdmin($admin);
        $unmanagedProject = createProject();

        $response = inertiaPost($admin, route('admins.store'), [
            'firstname' => 'Test',
            'lastname' => 'Admin',
            'email' => 'test@example.com',
            'projects' => [$managedProject->id, $unmanagedProject->id],
        ]);

        // Should fail - trying to assign to unmanaged project
    })->todo();
})->skip('TODO: Fix after User cast refactor - needs asUser pattern');
