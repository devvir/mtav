<?php

use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Family Model', function () {
    it('belongs to a project', function () {
        $family = Family::find(4); // Family #4 from universe
        $project = Project::find(1);

        expect($family->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe($project->id);
    });

    it('has many members', function () {
        $family = Family::find(4); // Family #4 with 3 members (#102, #103, #104)

        expect($family->members)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($family->members->count())->toBeGreaterThan(0);
    });

    it('can add a member to the family', function () {
        $family = Family::find(1); // Family #1 (no members)
        $member = Member::factory()->create();

        $family->addMember($member);

        expect($member->fresh()->family_id)->toBe($family->id);
    });

});

describe('Family Business Logic - Atomic Operations', function () {




});
