<?php

/**
 * Tests for creating Member users (sending invitations).
 *
 * Covers:
 * - Authorization: who can create members
 * - Family assignment validation
 * - Email and name validation
 * - Malicious data rejection
 */

use App\Notifications\MemberInvitationNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

uses()->group('Feature.Invitation');

beforeEach(function () {
    Mail::fake();
});

describe('When attempting to invite a Member', function () {
    it('succeeds when done by an Admin for a Project they manage', function () {
        $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 2, // Family in Project #1, managed by Admin #11
        ]);

        expect(Member::where('email', 'newmember@example.com'))->toExist();
    });

    it('succeeds when done by a Member for their own Family', function () {
        $this->submitFormToRoute('members.store', asMember: 102, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 4, // Same family
        ]);

        expect(Member::where('email', 'newmember@example.com'))->toExist();
    });

    it('invites them to the current Member\'s own family (if a regular Member is logged in) ignoring attempts to override the invited Member\'s Family', function () {
        $response = $this->submitFormToRoute('members.store', asMember: 102, redirects: false, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 5, // Different Family
        ]);

        // Should ignore the family_id and use the current Member's Family
        expect(Member::where([
            'email'     => 'newmember@example.com',
            'family_id' => 4,
        ]))->toExist();
    });

    it('fails when attempted by guests', function () {
        $response = $this->submitFormToRoute('members.store', redirects: false, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        $response->assertRedirect(route('login'));
    });

    it('fails when Admins attempt to create Members in unmanaged Projects', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 13, // Family in Project 2 (not managed by Admin)
        ]);

        expect(inertiaErrors($response))->toHaveKey('family_id');
    });

    it('fails when using non-existent Family IDs', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 99999,
        ]);

        expect(inertiaErrors($response))->toHaveKey('family_id');
    });
});

describe('For successfully inviting a Member', function () {
    it('requires a Family to be specified', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
        ]);

        expect(inertiaErrors($response))->toHaveKey('family_id');
    });

    it('requires an email', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        expect(inertiaErrors($response))->toHaveKey('email');
    });

    it('requires the email to have a valid format', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'not-an-email',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        expect(inertiaErrors($response))->toHaveKey('email');
    });

    it('requires the email to be globally unique', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'member102@example.com', // Already exists (Member #102)
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        expect(inertiaErrors($response))->toHaveKey('email');
    });

    it('requires a firstname', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        expect(inertiaErrors($response))->toHaveKey('firstname');
    });

    it('requires the firstname to be at least 2 characters', function () {
        $response = $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'J',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        expect(inertiaErrors($response))->toHaveKey('firstname');
    });

    it('allows an optional lastname', function () {
        $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'family_id' => 4,
        ]);

        expect(Member::where('email', 'newmember@example.com'))->toExist();
    });
});

describe('Upon a successful Member invitation', function () {
    it('creates a Member with the right state: Member role, unverified and invited', function () {
        $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        expect(User::firstWhere('email', 'newmember@example.com'))
            ->toBeMember()
            ->family_id->toBe(4)
            ->email_verified_at->toBeNull()
            ->invitation_accepted_at->toBeNull();
    });

    it('sends an invitation email', function () {
        /** Avoid running inside a transaction (SendUserInvitation implements ShouldQueueAfterCommit) */
        DB::rollback();
        Notification::fake();

        $this->submitFormToRoute('members.store', asAdmin: 11, data: [
            'email'     => 'newmember@example.com',
            'firstname' => 'Jane',
            'lastname'  => 'Doe',
            'family_id' => 4,
        ]);

        Notification::assertSentTo(
            Member::where('email', 'newmember@example.com')->first(),
            MemberInvitationNotification::class
        );
    });
});
