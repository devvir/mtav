<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Family Policy', function () {
    beforeEach(function () {
        // Prevent accidental superadmin bypass in policy tests
        // Gate::before() allows all superadmins, so we need to ensure
        // test users aren't accidentally superadmins
        config(['auth.superadmins' => []]);
    });

    it('allows anyone to view families', function () {
        $member = Member::factory()->create();
        $family = Family::factory()->create();

        expect($member->can('viewAny', Family::class))->toBeTrue()
            ->and($member->can('view', $family))->toBeTrue();
    });

    it('allows admins to create families', function () {
        $admin = Admin::factory()->create();

        expect($admin->can('create', Family::class))->toBeTrue();
    });

    it('denies members to create families', function () {
        $member = Member::factory()->create();

        expect($member->can('create', Family::class))->toBeFalse();
    });

    it('allows admins to update any family', function () {
        $admin = Admin::factory()->create();
        $family = Family::factory()->create();

        expect($admin->can('update', $family))->toBeTrue();
    });

    it('allows member to update their own family', function () {
        $family = Family::factory()->create();
        $member = Member::factory()->create(['family_id' => $family->id]);

        expect($member->can('update', $family))->toBeTrue();
    });

    it('denies member to update other families', function () {
        $family = Family::factory()->create();
        $member = Member::factory()->create(); // Different family

        expect($member->can('update', $family))->toBeFalse();
    });

    it('allows admins to delete families', function () {
        $admin = Admin::factory()->create();
        $family = Family::factory()->create();

        expect($admin->can('delete', $family))->toBeTrue();
    });

    it('denies members to delete families', function () {
        $family = Family::factory()->create();
        $member = Member::factory()->create(['family_id' => $family->id]);

        expect($member->can('delete', $family))->toBeFalse();
    });
});

describe('Family Policy - Project Scope - TODO', function () {
    test('admins can only create families in projects they manage', function () {
        // TODO: Create policy should validate that the target project_id
        // is one that the admin manages
    })->todo();

    test('admins can only update families in projects they manage', function () {
        // TODO: Update policy should check admin manages family.project
    })->todo();

    test('admins can only delete families in projects they manage', function () {
        // TODO: Delete policy should check admin manages family.project
    })->todo();

    test('superadmins bypass project scope restrictions', function () {
        // TODO: Superadmins should be able to manage families across all projects
    })->todo();
});
