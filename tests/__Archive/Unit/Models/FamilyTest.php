<?php

use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Family Model', function () {
    it('belongs to a project', function () {
        $project = Project::factory()->create();
        $family = Family::factory()->create(['project_id' => $project->id]);

        expect($family->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe($project->id);
    });

    it('has many members', function () {
        $family = Family::factory()->create();
        $member1 = Member::factory()->create(['family_id' => $family->id]);
        $member2 = Member::factory()->create(['family_id' => $family->id]);

        expect($family->members)->toHaveCount(2)
            ->and($family->members->pluck('id'))->toContain($member1->id, $member2->id);
    });

    it('can add a member to the family', function () {
        $family = Family::factory()->create();
        $member = Member::factory()->create();

        $family->addMember($member);

        expect($member->fresh()->family_id)->toBe($family->id);
    });

});

describe('Family Business Logic - Atomic Operations', function () {




});
