<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\Project;

describe('Member Policy', function () {
    beforeEach(function () {
        // Prevent accidental superadmin bypass in policy tests
        config(['auth.superadmins' => []]);
    });

    it('allows anyone to view members', function () {
        $viewer = Member::factory()->create();
        $member = Member::factory()->create();

        expect($viewer->can('viewAny', Member::class))->toBeTrue()
            ->and($viewer->can('view', $member))->toBeTrue();
    });

    it('allows anyone to create members', function () {
        $member = Member::factory()->create();

        expect($member->can('create', Member::class))->toBeTrue();
    });

    it('allows admins to update any member', function () {
        $admin = Admin::factory()->create();
        $member = Member::factory()->create();

        expect($admin->can('update', $member))->toBeTrue();
    });

    it('allows members to update themselves', function () {
        $member = Member::factory()->create();

        expect($member->can('update', $member))->toBeTrue();
    });

    it('denies members to update other members', function () {
        $member1 = Member::factory()->create();
        $member2 = Member::factory()->create();

        expect($member1->can('update', $member2))->toBeFalse();
    });

    it('allows admins to delete any member', function () {
        $admin = Admin::factory()->create();
        $member = Member::factory()->create();

        expect($admin->can('delete', $member))->toBeTrue();
    });

    it('allows members to delete themselves', function () {
        $member = Member::factory()->create();

        expect($member->can('delete', $member))->toBeTrue();
    });

    it('denies members to delete other members', function () {
        $member1 = Member::factory()->create();
        $member2 = Member::factory()->create();

        expect($member1->can('delete', $member2))->toBeFalse();
    });
});

describe('Member Policy - Project Scope - TODO', function () {
    test('members can only invite to their own family', function () {
        // TODO: Create policy should validate that if the user is a member,
        // the family_id must match their own family_id
    })->todo();

    test('admins can only create members in projects they manage', function () {
        // TODO: Create policy should validate that the target project_id
        // is one that the admin manages
    })->todo();

    test('admins can only update members in projects they manage', function () {
        // TODO: Update policy should check admin manages member.project
    })->todo();

    test('admins can only delete members in projects they manage', function () {
        // TODO: Delete policy should check admin manages member.project
    })->todo();
});
