<?php

use App\Models\Admin;
use App\Models\Member;

describe('Member Policy', function () {
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



});
