<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Family Policy', function () {
    it('allows anyone to view families', function () {
        $member = createMember(asUser: true);
        $family = createFamily();

        expect($member->can('viewAny', Family::class))->toBeTrue()
            ->and($member->can('view', $family))->toBeTrue();
    });

    it('allows admins to create families', function () {
        $admin = createAdmin(asUser: true);

        expect($admin->can('create', Family::class))->toBeTrue();
    });

    it('denies members to create families', function () {
        $member = createMember(asUser: true);

        expect($member->can('create', Family::class))->toBeFalse();
    });

    it('allows admins to update any family', function () {
        $family = createFamily();
        $admin = createAdminWithProjects([$family->project], asUser: true);

        expect($admin->can('update', $family))->toBeTrue();
    });

    it('allows member to update their own family', function () {
        $family = createFamily();
        $member = createMember(['family_id' => $family->id], asUser: true);

        expect($member->can('update', $family))->toBeTrue();
    });

    it('denies member to update other families', function () {
        $family = createFamily();
        $otherFamily = createFamily(); // Create a different family
        $member = createMember(['family_id' => $otherFamily->id], asUser: true);

        expect($member->can('update', $family))->toBeFalse();
    });

    it('allows admins to delete families', function () {
        $family = createFamily();
        $admin = createAdminWithProjects([$family->project], asUser: true);

        expect($admin->can('delete', $family))->toBeTrue();
    });

    it('denies members to delete families', function () {
        $family = createFamily();
        $member = createMember(['family_id' => $family->id], asUser: true);

        expect($member->can('delete', $family))->toBeFalse();
    });
});

describe('Family Policy - Project Scope - TODO', function () {



});
