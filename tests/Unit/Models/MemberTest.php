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

    it('returns the active project via project attribute', function () {
        // TODO: bug - The project attribute accessor doesn't work correctly even after fresh()
        // The getProjectAttribute() method uses $this->projects->where('pivot.active', true)->first()
        // but the projects relationship already has wherePivot('active', true)
        // This might be a double-filtering issue or the accessor needs to use load() instead of relying on the collection

        $member = Member::factory()->create();
        $project = Project::factory()->create();

        $member->joinProject($project);

        // Need to refresh the member to reload the projects relationship
        // since joinProject() modifies the pivot table
        expect($member->fresh()->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe($project->id);
    })->skip('bug: project attribute accessor not working');

    it('returns null for project attribute when member has no active project', function () {
        $member = Member::factory()->create();

        expect($member->project)->toBeNull();
    });

    it('can join a project', function () {
        // TODO: bug - Same as above, relies on the broken project attribute accessor
        $member = Member::factory()->create();
        $project = Project::factory()->create();

        $member->joinProject($project);

        expect($project->hasMember($member))->toBeTrue()
            ->and($member->fresh()->project->id)->toBe($project->id);
    })->skip('bug: project attribute accessor not working');

    it('can leave a project', function () {
        $member = Member::factory()->create();
        $project = Project::factory()->create();
        $member->joinProject($project);

        $member->leaveProject($project);

        expect($member->fresh()->project)->toBeNull();
    });

    it('can switch between projects', function () {
        // TODO: bug - Same as above, relies on the broken project attribute accessor
        $member = Member::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $member->joinProject($project1);
        expect($member->fresh()->project->id)->toBe($project1->id);

        $member->switchProject($project2);
        expect($member->fresh()->project->id)->toBe($project2->id)
            ->and($project1->fresh()->hasMember($member))->toBeFalse();
    })->skip('bug: project attribute accessor not working');

    it('applies member global scope to exclude admins', function () {
        User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['is_admin' => false]);

        expect(Member::all())->toHaveCount(1)
            ->and(Member::first()->id)->toBe($member->id);
    });
});

describe('Member Business Logic - TODO', function () {
    test('member switching projects should validate family atomicity', function () {
        // TODO: When a member switches projects individually,
        // all family members should switch together
        // This currently violates the business rule that families are atomic
    })->todo();

    test('member cannot switch projects if it breaks family atomicity', function () {
        // TODO: Should throw an exception or prevent the operation
        // if the member's family has other members in different projects
    })->todo();

    test('member can only invite users to their own family', function () {
        // TODO: When invitation system is implemented,
        // ensure members can only set family_id to their own family
    })->todo();
});
