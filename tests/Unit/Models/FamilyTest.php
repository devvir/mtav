<?php

use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;

describe('Family Model Relations', function () {
    it('belongs to a Project', function () {
        $family = Family::find(4); // Family #4 from universe

        expect($family->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe(1);
    });

    it('belongs to a Unit Type', function () {
        $family = Family::find(4); // Family #4 has Unit Type #1

        expect($family->unitType)
            ->toBeInstanceOf(UnitType::class)
            ->id->toBe(1);
    });

    it('has many Members', function () {
        $family = Family::find(4); // Family #4 with members #102-#104

        expect($family->members->pluck('id'))->toContain(102);
    });

    it('has no Members when the Family is empty', function () {
        $family = Family::find(1); // Family #1 (no members)

        expect($family->members)->toBeEmpty();
    });

    it('has one Unit when assigned', function () {
        $family = Family::find(4);
        $unit = Unit::factory()->create(['family_id' => $family->id]);

        expect($family->unit)
            ->toBeInstanceOf(Unit::class)
            ->id->toBe($unit->id);
    });
});

describe('Family Business Logic', function () {
    it('can add a Member to the Family', function () {
        $family = Family::find(1); // Family #1 (no members)
        // Factory: Fresh member for isolated addMember() test
        $member = Member::factory()->create();

        $family->addMember($member);

        expect($member->fresh()->family_id)->toBe($family->id);
    });

    it('can join a Project, bringing its Members along', function () {
        $family = Family::find(13); // Family #13 in Project #2, members #136-#138
        $project = Project::withoutGlobalScopes()->find(1);

        $family->join($project);

        expect($family->fresh()->project_id)->toBe(1)
            ->and($project->hasMember(Member::withoutGlobalScopes()->find(136)))->toBeTrue();
    });
});

describe('Family Model Scopes', function () {
    it('sorts alphabetically by name', function () {
        $names = Family::alphabetically()->pluck('name');

        expect($names->all())->toBe($names->sort()->values()->all());
    });

    it('filters to Families that have Members', function () {
        $familyIds = Family::withMembers()->pluck('id');

        expect($familyIds)->not->toContain(1) // Family #1 has no members
            ->and($familyIds)->toContain(4);
    });

    it('searches by Family name', function () {
        $results = Family::search('Family 4')->pluck('id');

        expect($results)->toContain(4);
    });

    it('searches by Member name when requested', function () {
        $member = Member::find(102); // Member #102 in Family #4

        $noMemberSearch = Family::search($member->firstname)->pluck('id');
        $memberSearch = Family::search($member->firstname, searchMembers: true)->pluck('id');

        expect($noMemberSearch)->not->toContain(4)
            ->and($memberSearch)->toContain(4);
    });
});
