<?php

use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;

describe('Member Model', function () {
    it('has a Family relationship', function () {
        $member = Member::find(102); // Member #102 from universe in Family #4
        $family = Family::find(4);

        expect($member->family)
            ->toBeInstanceOf(Family::class)
            ->id->toBe($family->id);
    });


    it('returns null for Project attribute when Member has no active Project', function () {
        $member = Member::factory()->create();

        expect($member->project)->toBeNull();
    });


    it('can leave a Project', function () {
        $project = Project::find(1);
        $member = Member::find(102); // Member in Project #1

        $member->leaveProject($project);

        expect($member->fresh()->project)->toBeNull();
    });
});

describe('Member Business Logic - TODO', function () {


});
