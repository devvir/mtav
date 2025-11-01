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

    it('has many families', function () {
        $project = Project::find(1); // Project #1 from universe
        $families = $project->families;

        expect($families)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($families->count())->toBeGreaterThan(0);
    });

    it('has many members through pivot table', function () {
        $project = Project::find(1); // Project #1 from universe

        expect($project->members)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($project->members->count())->toBeGreaterThan(0);
    });

    it('has many admins through pivot table', function () {
        $project = Project::find(1); // Project #1 from universe

        expect($project->admins)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($project->admins->count())->toBeGreaterThan(0);
    });

    it('can add a member to the project', function () {
        $project = Project::find(1);
        $member = Member::factory()->create();

        $project->addMember($member);

        expect($project->hasMember($member))->toBeTrue();
    });

    it('can remove a member from the project', function () {
        $project = Project::find(1);
        $member = Member::find(102); // Member from universe

        $project->removeMember($member);

        expect($member->fresh()->project)->toBeNull();
    });

    it('can add an admin to the project', function () {
        $project = Project::find(1);
        $admin = Admin::factory()->create();

        $project->addAdmin($admin);

        expect($project->hasAdmin($admin))->toBeTrue();
    });

    it('can check if it has a specific member', function () {
        $project = Project::find(1);
        $member = Member::find(102); // Member in project #1
        $nonMember = Member::find(136); // Member in project #2

        expect($project->hasMember($member))->toBeTrue()
            ->and($project->hasMember($nonMember))->toBeFalse();
    });

    it('can check if it has a specific admin', function () {
        $project = Project::find(1);
        $admin = Admin::find(11); // Admin in project #1
        $nonAdmin = Admin::find(12); // Admin in project #2

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
