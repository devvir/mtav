<?php

use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;

describe('Member Model', function () {
    it('has a family relationship', function () {
        $family = Family::factory()->create();
        $member = Member::factory()->create(['family_id' => $family->id]);

        expect($member->family)
            ->toBeInstanceOf(Family::class)
            ->id->toBe($family->id);
    });


    it('returns null for project attribute when member has no active project', function () {
        $member = Member::factory()->create();

        expect($member->project)->toBeNull();
    });


    it('can leave a project', function () {
        $member = Member::factory()->create();
        $project = Project::factory()->create();
        $member->joinProject($project);

        $member->leaveProject($project);

        expect($member->fresh()->project)->toBeNull();
    });


    it('applies member global scope to exclude admins', function () {
        User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['is_admin' => false]);

        expect(Member::all())->toHaveCount(1)
            ->and(Member::first()->id)->toBe($member->id);
    });
});

describe('Member Business Logic - TODO', function () {


});
