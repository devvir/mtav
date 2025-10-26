<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Invitation System - TODO', function () {
    test('no open registration - all users created by invitation', function () {
        // TODO: Verify no public registration routes exist
        // Only authenticated users can create other users
    })->todo();

    test('superadmin can invite anyone to any project', function () {
        // TODO: Superadmin should be able to create admins and members
        // for any project
    })->todo();

    test('admin can invite admins to their managed projects', function () {
        // TODO: Admin creating another admin should only be able to
        // assign them to projects the admin manages
    })->todo();

    test('admin can invite members to their managed projects', function () {
        // TODO: Admin creating members should only be able to
        // assign to families in projects they manage
    })->todo();

    test('member can invite family members to their own family', function () {
        // TODO: Member creating another member should:
        // 1. Automatically set family_id to their own family
        // 2. Automatically set project to their family's project
        // 3. Not allow selecting different family
    })->todo();

    test('invitation creates user with pending verification', function () {
        // TODO: Invited users should have email_verified_at = null
        // and receive verification email
    })->todo();

    test('invited user receives email with setup link', function () {
        // TODO: Email should contain link to set password and verify
    })->todo();

    test('invited user can set password and activate account', function () {
        // TODO: Test the activation flow
    })->todo();

    test('invitation expires after certain period', function () {
        // TODO: Consider implementing invitation expiry
    })->todo();

    test('cannot invite with duplicate email', function () {
        // TODO: Validate email uniqueness
    })->todo();
});
