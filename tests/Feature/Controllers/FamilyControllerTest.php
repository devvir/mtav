<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Family Controller - Index', function () {
    it('lists families for the current project', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);
        setState('project', $project);

        $family1 = Family::factory()->create(['project_id' => $project->id]);
        $family2 = Family::factory()->create(['project_id' => $project->id]);
        $otherFamily = Family::factory()->create(); // Different project

        $response = $this->actingAs($admin)->get(route('families.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Families/Index')
                ->has('families.data', 2)
            );
    });

    it('searches families by name', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);
        session(['project' => $project]);

        Family::factory()->create(['project_id' => $project->id, 'name' => 'Smith Family']);
        Family::factory()->create(['project_id' => $project->id, 'name' => 'Jones Family']);

        $response = $this->actingAs($admin)->get(route('families.index', ['q' => 'Smith']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('families.data', 1)
                ->where('families.data.0.name', 'Smith Family')
            );
    });
});

describe('Family Controller - Create/Store', function () {
    it('allows admins to create families', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);

        $response = $this->actingAs($admin)->get(route('families.create'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Families/Create')
                ->has('projects')
            );
    });

    it('stores a new family', function () {
        $admin = Admin::factory()->create();
        $project = Project::factory()->create();
        $project->addAdmin($admin);

        $response = $this->actingAs($admin)->post(route('families.store'), [
            'name' => 'Test Family',
            'project' => $project->id,
        ]);

        expect(Family::where('name', 'Test Family')->exists())->toBeTrue();
        $response->assertRedirect(route('families.show', Family::first()->id));
    });

    it('denies members from creating families', function () {
        $member = Member::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($member)->post(route('families.store'), [
            'name' => 'Test Family',
            'project' => $project->id,
        ]);

        $response->assertForbidden();
    });
});

describe('Family Controller - TODO', function () {
    test('admin can only create family in projects they manage', function () {
        // TODO: Validate that admin cannot create family in unmanaged project
    })->todo();

    test('family creation automatically sets project_id', function () {
        // TODO: Verify the redundant project_id is set correctly
    })->todo();

    test('cannot delete family that has members', function () {
        // TODO: Should prevent deletion or cascade properly
    })->todo();
});
