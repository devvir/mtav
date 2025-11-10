<?php

use App\Models\Project;

describe('Project CRUD - Index/Show', function () {
    it('allows Admin to view Project they manage', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());

        $response = inertiaGet($admin, route('projects.show', $project));

        assertInertiaComponent($response, 'Projects/Show');
    });

    it('allows Superadmin to view any Project', function () {
        $superadmin = createSuperAdmin(asUser: true);
        $project = createProject();

        $response = inertiaGet($superadmin, route('projects.show', $project));

        assertInertiaComponent($response, 'Projects/Show');
    });
});

describe('Project CRUD - Update', function () {




});

describe('Project CRUD - Delete', function () {
    it('allows Admin to delete Project they manage', function () {
        $admin = createAdmin(asUser: true);
        $project = createProject();
        $project->addAdmin($admin->asAdmin());

        $response = inertiaDelete($admin, route('projects.destroy', $project));

        expect(Project::withTrashed()->find($project->id)->deleted_at)->not->toBeNull();
        $response->assertRedirect();
    });



});

describe('Project CRUD - Create (Superadmin Only)', function () {
});
