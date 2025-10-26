<?php

use App\Models\Unit;

describe('Unit CRUD - Index/Show (All Users)', function () {
    it('lists units for current project', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        setCurrentProject($project);

        $unit1 = Unit::factory()->create(['project_id' => $project->id]);
        $unit2 = Unit::factory()->create(['project_id' => $project->id]);
        $otherUnit = Unit::factory()->create(); // Different project

        $response = inertiaGet($admin, route('units.index'));

        assertInertiaComponent($response, 'Units/Index');
        $units = getInertiaProp($response, 'units');
        expect($units)->toHaveCount(2);
    });

    it('allows members to view units', function () {
        $project = createProject();
        setCurrentProject($project);
        $member = createMemberInProject($project);
        $unit = Unit::factory()->create(['project_id' => $project->id]);

        $response = inertiaGet($member, route('units.index'));

        assertInertiaComponent($response, 'Units/Index');
    });

    it('allows anyone to view unit details', function () {
        $member = createMember();
        $unit = Unit::factory()->create();

        $response = inertiaGet($member, route('units.show', $unit));

        assertInertiaComponent($response, 'Units/Show');
    });
});

describe('Unit CRUD - Create/Store (Admin Only)', function () {
    it('allows admins to create units', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        setCurrentProject($project);

        $response = inertiaGet($admin, route('units.create'));

        assertInertiaComponent($response, 'Units/Create');
    });

    it('denies members from accessing unit creation form', function () {
        $project = createProject();
        setCurrentProject($project);
        $member = createMemberInProject($project);

        $response = inertiaGet($member, route('units.create'));

        $response->assertForbidden();
    });

    it('allows admin to create unit in project they manage', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        setCurrentProject($project);

        $response = inertiaPost($admin, route('units.store'), [
            'number' => 'A-101',
            'floor' => 1,
            'bedrooms' => 2,
            'bathrooms' => 1,
        ]);

        $unit = Unit::where('number', 'A-101')->first();
        expect($unit)->not->toBeNull()
            ->and($unit->project_id)->toBe($project->id);

        $response->assertRedirect(route('units.show', $unit->id));
    });

    it('denies admin from creating unit without current project set', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        // Not setting current project

        $response = inertiaPost($admin, route('units.store'), [
            'number' => 'A-101',
            'floor' => 1,
        ]);

        // Should fail because no current project
        $response->assertStatus(302); // Redirect or error
    });

    it('denies members from creating units', function () {
        $project = createProject();
        setCurrentProject($project);
        $member = createMemberInProject($project);

        $response = inertiaPost($member, route('units.store'), [
            'number' => 'Hacked-101',
            'floor' => 1,
        ]);

        $response->assertForbidden();
        expect(Unit::where('number', 'Hacked-101')->exists())->toBeFalse();
    });

    it('validates required fields on creation', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        setCurrentProject($project);

        $response = inertiaPost($admin, route('units.store'), [
            'number' => '', // Invalid
        ]);

        assertInertiaHasError($response, 'number');
    });
});

describe('Unit CRUD - Update (Admin Only)', function () {
    it('allows admin to update unit in project they manage', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        $unit = Unit::factory()->create(['project_id' => $project->id]);

        $response = inertiaPatch($admin, route('units.update', $unit), [
            'number' => 'Updated-101',
            'floor' => $unit->floor,
        ]);

        expect($unit->fresh()->number)->toBe('Updated-101');
        $response->assertRedirect();
    });

    it('denies admin from updating unit in project they do not manage', function () {
        $admin = createAdmin();
        createProjectWithAdmin($admin); // Manages this
        $otherProject = createProject(); // Not this
        $unit = Unit::factory()->create(['project_id' => $otherProject->id]);

        $response = inertiaPatch($admin, route('units.update', $unit), [
            'number' => 'Unauthorized',
            'floor' => $unit->floor,
        ]);

        $response->assertForbidden();
        expect($unit->fresh()->number)->not->toBe('Unauthorized');
    });

    it('allows superadmin to update any unit', function () {
        $superadmin = createSuperAdmin();
        $unit = Unit::factory()->create();

        $response = inertiaPatch($superadmin, route('units.update', $unit), [
            'number' => 'SuperAdmin-Updated',
            'floor' => $unit->floor,
        ]);

        expect($unit->fresh()->number)->toBe('SuperAdmin-Updated');
    });

    it('denies members from updating units', function () {
        $project = createProject();
        $member = createMemberInProject($project);
        $unit = Unit::factory()->create(['project_id' => $project->id]);

        $response = inertiaPatch($member, route('units.update', $unit), [
            'number' => 'Hacked',
            'floor' => $unit->floor,
        ]);

        $response->assertForbidden();
    });
});

describe('Unit CRUD - Delete (Admin Only)', function () {
    it('allows admin to delete unit in project they manage', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        $unit = Unit::factory()->create(['project_id' => $project->id]);

        $response = inertiaDelete($admin, route('units.destroy', $unit));

        expect(Unit::find($unit->id))->toBeNull();
        $response->assertRedirect();
    });

    it('denies admin from deleting unit in project they do not manage', function () {
        $admin = createAdmin();
        createProjectWithAdmin($admin);
        $otherProject = createProject();
        $unit = Unit::factory()->create(['project_id' => $otherProject->id]);

        $response = inertiaDelete($admin, route('units.destroy', $unit));

        $response->assertForbidden();
        expect(Unit::find($unit->id))->not->toBeNull();
    });

    it('allows superadmin to delete any unit', function () {
        $superadmin = createSuperAdmin();
        $unit = Unit::factory()->create();

        $response = inertiaDelete($superadmin, route('units.destroy', $unit));

        expect(Unit::find($unit->id))->toBeNull();
    });

    it('denies members from deleting units', function () {
        $project = createProject();
        $member = createMemberInProject($project);
        $unit = Unit::factory()->create(['project_id' => $project->id]);

        $response = inertiaDelete($member, route('units.destroy', $unit));

        $response->assertForbidden();
    });
});

describe('Unit CRUD - Project Scope Enforcement', function () {
    it('creates unit in current project automatically', function () {
        $admin = createAdmin();
        $project = createProjectWithAdmin($admin);
        setCurrentProject($project);

        $response = inertiaPost($admin, route('units.store'), [
            'number' => 'A-101',
            'floor' => 1,
        ]);

        $unit = Unit::where('number', 'A-101')->first();
        expect($unit->project_id)->toBe($project->id);
    });

    test('admin cannot create unit in project they do not manage', function () {
        // TODO: Even if admin sets current project to one they don't manage,
        // validation should prevent unit creation
    })->todo();
});
