<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Member Controller - Index', function () {
    it('lists members for the current project', function () {
        $admin = createAdmin(asUser: true);
        $project = Project::factory()->create();
        $project->addAdmin($admin->asAdmin());
        session(['project' => $project]);

        $family = Family::factory()->create(['project_id' => $project->id]);
        $member1 = Member::factory()->create(['family_id' => $family->id]);
        $member2 = Member::factory()->create(['family_id' => $family->id]);
        $project->addMember($member1);
        $project->addMember($member2);

        $otherMember = Member::factory()->create(); // Different project

        $response = $this->actingAs($admin)->get(route('members.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Members/Index')
                ->has('members.data', 2)
            );
    });

    it('searches members by name, email, or family', function () {
        $admin = createAdmin(asUser: true);
        $project = Project::factory()->create();
        $project->addAdmin($admin->asAdmin());
        session(['project' => $project]);

        $family = Family::factory()->create(['project_id' => $project->id, 'name' => 'Smith Family']);
        $member = Member::factory()->create(['family_id' => $family->id, 'firstname' => 'John']);
        $project->addMember($member);

        $response = $this->actingAs($admin)->get(route('members.index', ['q' => 'John']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->has('members.data', 1));
    });
});

describe('Member Controller - Create/Store', function () {
    it('allows admins to create members', function () {
        $admin = createAdmin(asUser: true);
        $project = Project::factory()->create();
        $project->addAdmin($admin->asAdmin());
        session(['project' => $project]);

        $response = $this->actingAs($admin)->get(route('members.create'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Members/Create')
                ->has('families')
                ->has('projects')
            );
    });
});

describe('Member Controller - Update/Delete', function () {
    it('allows admins to update any member', function () {
        $project = createProject();
        $admin = createAdminWithProjects([$project], asUser: true);
        $member = createMemberInProject($project);

        $response = $this->actingAs($admin)->patch(route('members.update', $member), [
            'firstname' => 'Updated',
            'lastname' => $member->lastname,
            'email' => $member->email,
        ]);

        expect($member->fresh()->firstname)->toBe('Updated');
        $response->assertRedirect();
    });


});

describe('Member Controller - Business Logic - TODO', function () {



});
