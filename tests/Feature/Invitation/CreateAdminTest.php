<?php

// Copilot - pending review

/**
 * Tests for creating Admin users (sending invitations).
 *
 * Covers:
 * - Authorization: who can create admins
 * - Project assignment validation
 * - Email and name validation
 * - Malicious data rejection
 */

use App\Notifications\AdminInvitationNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

uses()->group('Feature.Invitation');

beforeEach(function () {
    Mail::fake();
});

describe('When attempting to invite an Admin', function () {
    it('succeeds when done by an Admin for Projects they manage', function () {
        $this->submitFormToRoute(
            'admins.store',
            asAdmin: 11,
            data: [
                'email'       => 'newadmin@example.com',
                'firstname'   => 'John',
                'lastname'    => 'Doe',
                'project_ids' => [1],
            ]
        );

        expect(Admin::where('email', 'newadmin@example.com'))->toExist();
    });

    it('fails when attempted by Members', function () {
        $response = $this->submitFormToRoute(
            'admins.store',
            asMember: 102,
            redirects: false,
            data: [
                'email'       => 'newadmin@example.com',
                'firstname'   => 'John',
                'lastname'    => 'Doe',
                'project_ids' => [1],
            ]
        );

        expect($response)->toRedirectTo('dashboard');
    });

    it('fails when attempted by guests', function () {
        $response = $this->submitFormToRoute(
            'admins.store',
            redirects: false,
            data: [
                'email'       => 'newadmin@example.com',
                'firstname'   => 'John',
                'lastname'    => 'Doe',
                'project_ids' => [1],
            ]
        );

        $response->assertRedirect(route('login'));
    });
});

describe('For successfully inviting an Admin', function () {
    it('requires at least one Project to be assigned', function () {
        $response = $this->submitFormToRoute(
            'admins.store',
            asAdmin: 11,
            data: [
                'email'       => 'newadmin@example.com',
                'firstname'   => 'John',
                'lastname'    => 'Doe',
                'project_ids' => [],
            ]
        );

        expect(inertiaErrors($response))->toHaveKey('project_ids');
    });

    it('enforces that only managed Projects can be assigned', function () {
        $response = $this->submitFormToRoute(
            'admins.store',
            asAdmin: 11,
            data: [
                'email'       => 'newadmin@example.com',
                'firstname'   => 'John',
                'lastname'    => 'Doe',
                'project_ids' => [1, 2], // Project 2 not managed by admin
            ]
        );

        expect(inertiaErrors($response))->toHaveKey('project_ids');
    });

    it('allows assigning multiple Projects when the inviter manages them', function () {
        $this->submitFormToRoute(
            'admins.store',
            asAdmin: 12,
            data: [
                'email'       => 'multiproject@example.com',
                'firstname'   => 'John',
                'lastname'    => 'Doe',
                'project_ids' => [2, 3],
            ]
        );

        $newAdmin = Admin::where('email', 'multiproject@example.com')->first()->fresh();
        expect($newAdmin->projects->pluck('id')->sort()->values()->toArray())->toBe([2, 3]);
    });

    it('rejects non-existent Project ids', function () {
        $response = $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'newadmin@example.com',
            'firstname'   => 'John',
            'lastname'    => 'Doe',
            'project_ids' => [99999],
        ]);

        expect(inertiaErrors($response))->toHaveKey('project_ids.0');
    });

    it('requires an email', function () {
        $response = $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'firstname'   => 'John',
            'lastname'    => 'Doe',
            'project_ids' => [1],
        ]);

        expect(inertiaErrors($response))->toHaveKey('email');
    });

    it('requires the email to have a valid format', function () {
        $response = $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'not-an-email',
            'firstname'   => 'John',
            'lastname'    => 'Doe',
            'project_ids' => [1],
        ]);

        expect(inertiaErrors($response))->toHaveKey('email');
    });

    it('requires the email to be globally unique', function () {
        $response = $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'admin12@example.com', // Already exists (Admin #12)
            'firstname'   => 'John',
            'lastname'    => 'Doe',
            'project_ids' => [1],
        ]);

        expect(inertiaErrors($response))->toHaveKey('email');
    });

    it('requires a firstname', function () {
        $response = $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'newadmin@example.com',
            'lastname'    => 'Doe',
            'project_ids' => [1],
        ]);

        expect(inertiaErrors($response))->toHaveKey('firstname');
    });

    it('requires the firstame to be at least 2 characters', function () {
        $response = $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'newadmin@example.com',
            'firstname'   => 'J',
            'lastname'    => 'Doe',
            'project_ids' => [1],
        ]);

        expect(inertiaErrors($response))->toHaveKey('firstname');
    });

    it('allows an optional lastname', function () {
        $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'newadmin@example.com',
            'firstname'   => 'John',
            'project_ids' => [1],
        ]);

        expect(Admin::where('email', 'newadmin@example.com'))->toExist();
    });
});

describe('Upon a successful Admin invitation', function () {
    it('creates an Admin with the right state: admin role, unverified and invited', function () {
        $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'newadmin@example.com',
            'firstname'   => 'John',
            'lastname'    => 'Doe',
            'project_ids' => [1],
        ]);

        $newAdmin = User::where('email', 'newadmin@example.com')->first();

        expect($newAdmin)->not->toBeNull()
            ->and($newAdmin->invitation_accepted_at)->toBeNull()
            ->and($newAdmin->email_verified_at)->toBeNull();

        expect($newAdmin)->toBeAdmin();
    });

    it('sends an invitation email', function () {
        /** Avoid running inside a transaction (SendUserInvitation implements ShouldQueueAfterCommit) */
        DB::rollback();
        Notification::fake();

        $this->submitFormToRoute('admins.store', asAdmin: 11, data: [
            'email'       => 'adminemail@example.com',
            'firstname'   => 'John',
            'lastname'    => 'Doe',
            'project_ids' => [1],
        ]);

        Notification::assertSentTo(
            Admin::where('email', 'adminemail@example.com')->first(),
            AdminInvitationNotification::class
        );
    });
});
