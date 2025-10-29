<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Family Controller - Index', function () {
    it('lists families for the current project', function () {
        $admin = createAdmin(asUser: true);
        $project = Project::factory()->create();
        $project->addAdmin($admin->asAdmin());
        session(['project' => $project]);

        $family1 = Family::factory()->create(['project_id' => $project->id]);
        $family2 = Family::factory()->create(['project_id' => $project->id]);
        $otherFamily = Family::factory()->create(); // Different project

        $response = $this->actingAs($admin)->get(route('families.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('families.data', 2)
            );
    });

    it('searches families by name', function () {
        $admin = createAdmin(asUser: true);
        $project = Project::factory()->create();
        $project->addAdmin($admin->asAdmin());
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
        $admin = createAdmin(asUser: true);
        $project = Project::factory()->create();
        $project->addAdmin($admin->asAdmin());

        $response = $this->actingAs($admin)->get(route('families.create'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Families/Create')
                ->has('projects')
            );
    });


});

describe('Family Controller - TODO', function () {


});
