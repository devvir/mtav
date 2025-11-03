<?php

// Copilot - pending review

/**
 * Tests for creating Member users (sending invitations).
 *
 * Covers:
 * - Authorization: who can create members
 * - Family assignment validation
 * - Email and name validation
 * - Malicious data rejection
 */

use App\Mail\MemberInvitationMail;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

uses()->group('Feature.Invitation');

beforeEach(function () {
    Mail::fake();
});

describe('When creating a Member', function () {
    describe('Authorization', function () {
        it('allows admin to create members for projects they manage', function () {
            $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4, // Family in project 1
            ]);

            expect(Member::where('email', 'newmember@example.com'))->toExist();
        });

        it('allows member to create members in their own family', function () {
            $this->postToRoute('members.store', asMember: 102, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4, // Same family
            ]);

            expect(Member::where('email', 'newmember@example.com'))->toExist();
        });

        it('prevents member from creating members in other families', function () {
            $response = $this->postToRoute('members.store', asMember: 102, redirects: false, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 5, // Different family
            ]);

            // Should ignore the family_id and use the current member's family
            expect(Member::where(['email' => 'newmember@example.com', 'family_id' => 4]))->toExist();
        })->group('testing');

        it('prevents guests from creating members', function () {
            $response = $this->postToRoute('members.store', redirects: false, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            $response->assertRedirect(route('login'));
        });
    });

    describe('Family assignment validation', function () {
        it('requires family_id', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
            ]);

            expect(inertiaErrors($response))->toHaveKey('family_id');
        });

        it('rejects admin creating member in family from unmanaged project', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 13, // Family in project 2 (not managed by admin)
            ]);

            expect(inertiaErrors($response))->toHaveKey('family_id');
        });

        it('rejects non-existent family id', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 99999,
            ]);

            expect(inertiaErrors($response))->toHaveKey('family_id');
        });
    });

    describe('Email validation', function () {
        it('requires email', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            expect(inertiaErrors($response))->toHaveKey('email');
        });

        it('requires valid email format', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'not-an-email',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            expect(inertiaErrors($response))->toHaveKey('email');
        });

        it('requires unique email', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'member102@example.com', // Already exists (Member #102)
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            expect(inertiaErrors($response))->toHaveKey('email');
        });
    });

    describe('Name validation', function () {
        it('requires firstname', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            expect(inertiaErrors($response))->toHaveKey('firstname');
        });

        it('requires firstname to be at least 2 characters', function () {
            $response = $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'J',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            expect(inertiaErrors($response))->toHaveKey('firstname');
        });

        it('allows optional lastname', function () {
            $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'family_id' => 4,
            ]);

            expect(Member::where('email', 'newmember@example.com'))->toExist();
        });
    });

    describe('Invitation creation', function () {
        it('creates member with invitation fields set correctly', function () {
            $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            $newMember = User::where('email', 'newmember@example.com')->first();
            expect($newMember)->not->toBeNull()
                ->and($newMember->invitation_accepted_at)->toBeNull()
                ->and($newMember->email_verified_at)->toBeNull()
                ->and($newMember->family_id)->toBe(4);

            expect($newMember)->toBeMember();
        });

        it('sends invitation email upon creation', function () {
            $this->postToRoute('members.store', asAdmin: 11, data: [
                'email' => 'newmember@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'family_id' => 4,
            ]);

            Mail::assertSent(MemberInvitationMail::class, function ($mail) {
                return $mail->hasTo('newmember@example.com');
            });
        });
    });
});
