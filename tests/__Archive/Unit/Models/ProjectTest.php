<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;

describe('Project Model', function () {
    it('has many units', function () {
        $project = Project::find(1); // Project #1 from universe

        expect($project->units)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

    it('has many Families', function () {
        $project = Project::find(1); // Project #1 from universe
        $families = $project->families;

        expect($families)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($families->count())->toBeGreaterThan(0);
    });

    it('has many Members through pivot table', function () {
        $project = Project::find(1); // Project #1 from universe

        expect($project->members)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($project->members->count())->toBeGreaterThan(0);
    });

    it('has many Admins through pivot table', function () {
        $project = Project::find(1); // Project #1 from universe

        expect($project->admins)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($project->admins->count())->toBeGreaterThan(0);
    });

    it('can add a Member to the Project', function () {
        $project = Project::find(1);
        $member = Member::factory()->create();

        $project->addMember($member);

        expect($project->hasMember($member))->toBeTrue();
    });

    it('can remove a Member from the Project', function () {
        $project = Project::find(1);
        $member = Member::find(102); // Member from Universe

        $project->removeMember($member);

        expect($member->fresh()->project)->toBeNull();
    });

    it('can add an Admin to the Project', function () {
        $project = Project::find(1);
        $admin = Admin::factory()->create();

        $project->addAdmin($admin);

        expect($project->hasAdmin($admin))->toBeTrue();
    });

    it('can check if it has a specific Member', function () {
        $project = Project::find(1);
        $member = Member::find(102); // Member from Project #1
        $nonMember = Member::find(136); // Member in Project #2

        expect($project->hasMember($member))->toBeTrue()
            ->and($project->hasMember($nonMember))->toBeFalse();
    });

    it('can check if it has a specific Admin', function () {
        $project = Project::find(1);
        $admin = Admin::find(11); // Admin in Project #1
        $nonAdmin = Admin::find(12); // Admin in Project #2

        expect($project->hasAdmin($admin))->toBeTrue()
            ->and($project->hasAdmin($nonAdmin))->toBeFalse();
    });

    it('has alphabetically scope', function () {
        Project::factory()->create(['name' => 'Zebra']);
        Project::factory()->create(['name' => 'Alpha']);
        Project::factory()->create(['name' => 'Beta']);

        $projects = Project::alphabetically()->get();

        expect($projects->first()->name)->toBe('Alpha')
            ->and($projects->last()->name)->toBe('Zebra');
    });
});
