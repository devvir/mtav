<?php

use App\Models\Project;

describe('Project CRUD - Index/Show', function () {
    it('allows superadmins to view all projects', function () {
        $superadmin = createSuperAdmin();
        $projects = createProjects(3);

        $response = inertiaGet($superadmin, route('projects.index'));

        assertInertiaPaginatedData($response, 'Projects/Index', 'projects', 3);
    });

    it('allows admins with multiple projects to view project list', function () {
        $admin = createAdmin();
        $project1 = createProjectWithAdmin($admin);
        $project2 = createProjectWithAdmin($admin);
        createProject(); // Other project

        $response = inertiaGet($admin, route('projects.index'));

        assertInertiaPaginatedData($response, 'Projects/Index', 'projects', 2);
    });

    it('denies admins with single project from viewing project list', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaGet($admin, route('projects.index'));

        $response->assertForbidden();
    });

    it('denies members from viewing project list', function () {
        $member = createMember();

        $response = inertiaGet($member, route('projects.index'));

        $response->assertForbidden();
    });

    it('allows admin to view project they manage', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaGet($admin, route('projects.show', $project));

        assertInertiaComponent($response, 'Projects/Show');
    });

    it('denies admin from viewing project they do not manage', function () {
        $admin = createAdmin();
        $project = createProject();

        $response = inertiaGet($admin, route('projects.show', $project));

        $response->assertForbidden();
    });

    it('allows superadmin to view any project', function () {
        $superadmin = createSuperAdmin();
        $project = createProject();

        $response = inertiaGet($superadmin, route('projects.show', $project));

        assertInertiaComponent($response, 'Projects/Show');
    });

    it('allows members to view their project', function () {
        $project = createProject();
        $member = createMemberInProject($project);

        $response = inertiaGet($member, route('projects.show', $project));

        assertInertiaComponent($response, 'Projects/Show');
    });

    it('filters project list by admin managed projects only', function () {
        $admin = createAdmin();
        $managedProject1 = createProjectWithAdmin($admin);
        $managedProject2 = createProjectWithAdmin($admin);
        $unmanagedProject = createProject();

        $response = inertiaGet($admin, route('projects.index'));

        $projects = getInertiaProp($response, 'projects.data');
        $projectIds = collect($projects)->pluck('id')->toArray();

        expect($projectIds)->toContain($managedProject1->id, $managedProject2->id)
            ->not->toContain($unmanagedProject->id);
    });
});

describe('Project CRUD - Update', function () {
    it('allows admin to update project they manage', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaPatch($admin, route('projects.update', $project), [
            'name' => 'Updated Name',
            'description' => $project->description,
            'organization' => $project->organization,
        ]);

        expect($project->fresh()->name)->toBe('Updated Name');
        $response->assertRedirect();
    });

    it('denies admin from updating project they do not manage', function () {
        $admin = createAdmin();
        $project = createProject();

        $response = inertiaPatch($admin, route('projects.update', $project), [
            'name' => 'Hacked',
            'description' => $project->description,
            'organization' => $project->organization,
        ]);

        $response->assertForbidden();
        expect($project->fresh()->name)->not->toBe('Hacked');
    });

    it('allows superadmin to update any project', function () {
        $superadmin = createSuperAdmin();
        $project = createProject();

        $response = inertiaPatch($superadmin, route('projects.update', $project), [
            'name' => 'Updated by SuperAdmin',
            'description' => $project->description,
            'organization' => $project->organization,
        ]);

        expect($project->fresh()->name)->toBe('Updated by SuperAdmin');
    });

    it('denies members from updating projects', function () {
        $project = createProject();
        $member = createMemberInProject($project);

        $response = inertiaPatch($member, route('projects.update', $project), [
            'name' => 'Hacked',
            'description' => $project->description,
            'organization' => $project->organization,
        ]);

        $response->assertForbidden();
    });

    it('validates required fields on update', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaPatch($admin, route('projects.update', $project), [
            'name' => '', // Invalid
        ]);

        assertInertiaHasError($response, 'name');
    });
});

describe('Project CRUD - Delete', function () {
    it('allows admin to delete project they manage', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);

        $response = inertiaDelete($admin, route('projects.destroy', $project));

        expect(Project::find($project->id))->toBeNull();
        $response->assertRedirect();
    });

    it('denies admin from deleting project they do not manage', function () {
        $admin = createAdmin();
        $project = createProject();

        $response = inertiaDelete($admin, route('projects.destroy', $project));

        $response->assertForbidden();
        expect(Project::find($project->id))->not->toBeNull();
    });

    it('allows superadmin to delete any project', function () {
        $superadmin = createSuperAdmin();
        $project = createProject();

        $response = inertiaDelete($superadmin, route('projects.destroy', $project));

        expect(Project::find($project->id))->toBeNull();
    });

    it('denies members from deleting projects', function () {
        $project = createProject();
        $member = createMemberInProject($project);

        $response = inertiaDelete($member, route('projects.destroy', $project));

        $response->assertForbidden();
    });
});

describe('Project CRUD - Create (Superadmin Only)', function () {
    test('only superadmins can create projects', function () {
        // TODO: Implement project creation tests when routes are added
        // Only superadmins should be able to create projects
    })->todo();
});
