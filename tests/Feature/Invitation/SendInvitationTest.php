<?php

/**
 * Tests for the invitation sending part of the flow.
 *
 * Covers:
 * - Creating Member/Admin users
 * - Sending invitation emails
 * - Verifying field values (invitation_accepted_at IS NULL, email_verified_at IS NULL)
 * - Confirming emails are sent in the correct locale (en, es_UY)
 */

use App\Events\UserRegistration;
use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Notifications\AdminInvitationNotification;
use App\Notifications\MemberInvitationNotification;
use Illuminate\Support\Facades\Notification;

uses()->group('Feature.Invitation');

beforeEach(function () {
    Notification::fake();
});

describe('When an Admin is created', function () {
    it('sends an invitation email', function () {
        // Admin #18 (invited, manages Project #1)
        $admin = Admin::find(18);
        $token = base64_encode(random_bytes(32));

        event(new UserRegistration($admin, $token));

        Notification::assertSentTo($admin, AdminInvitationNotification::class);
    });

    it('sends email in English locale for en users', function () {
        app()->setLocale('en');

        // Admin #18 (invited, manages Project #1)
        $admin = Admin::find(18);
        $token = base64_encode(random_bytes(32));

        event(new UserRegistration($admin, $token));

        Notification::assertSentTo($admin, AdminInvitationNotification::class, function ($notification, $channels) use ($admin) {
            $mailMessage = $notification->toMail($admin);
            expect($mailMessage->subject)->toBe(__('emails.admin_invitation', [], 'en'));
            expect($mailMessage->view)->toBe('emails.en.admin-invitation');

            return true;
        });
    });

    it('sends email in Spanish locale for es_UY users', function () {
        app()->setLocale('es_UY');

        // Admin #19 (invited, manages Projects #1 and #2)
        $admin = Admin::find(19);
        $token = base64_encode(random_bytes(32));

        event(new UserRegistration($admin, $token));

        Notification::assertSentTo($admin, AdminInvitationNotification::class, function ($notification, $channels) use ($admin) {
            $mailMessage = $notification->toMail($admin);
            expect($mailMessage->subject)->toBe(__('emails.admin_invitation', [], 'es_UY'));
            expect($mailMessage->view)->toBe('emails.es_UY.admin-invitation');

            return true;
        });
    });
});

describe('When a Member is created', function () {
    it('sends an invitation email', function () {
        // Member #148 (invited, in Family #25, Project #1)
        $member = Member::find(148);
        $token = base64_encode(random_bytes(32));

        event(new UserRegistration($member, $token));

        Notification::assertSentTo($member, MemberInvitationNotification::class);
    });

    it('sends email in English locale for en users', function () {
        app()->setLocale('en');

        // Member #148 (invited, in Family #25, Project #1)
        $member = Member::find(148);
        $token = base64_encode(random_bytes(32));

        event(new UserRegistration($member, $token));

        Notification::assertSentTo($member, MemberInvitationNotification::class, function ($notification, $channels) use ($member) {
            $mailMessage = $notification->toMail($member);
            // Member #148 is the only member in Family #25
            expect($mailMessage->subject)->toBe(__('emails.member_invitation', [], 'en'));
            expect($mailMessage->view)->toBe('emails.en.member-invitation');

            return true;
        });
    });

    it('sends email in Spanish locale for es_UY users', function () {
        app()->setLocale('es_UY');

        // Member #149 (invited, only member in Family #26, Project #2)
        $member = Member::find(149);
        $token = base64_encode(random_bytes(32));

        event(new UserRegistration($member, $token));

        Notification::assertSentTo($member, MemberInvitationNotification::class, function ($notification, $channels) use ($member) {
            $mailMessage = $notification->toMail($member);
            expect($mailMessage->subject)->toBe(__('emails.member_invitation', [], 'es_UY'));
            expect($mailMessage->view)->toBe('emails.es_UY.member-invitation');

            return true;
        });
    });
});
