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
use App\Mail\AdminInvitationMail;
use App\Mail\MemberInvitationMail;
use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use Illuminate\Support\Facades\Mail;

uses()->group('Feature.Invitation');

describe('When an Admin is created', function () {
    it('sends an invitation email', function () {
        Mail::fake();

        $project = Project::factory()->create();
        $token = base64_encode(random_bytes(32));

        $admin = Admin::factory()->create([
            'password' => $token,
            'invitation_accepted_at' => null,
            'email_verified_at' => null,
        ]);
        $admin->projects()->attach($project);

        event(new UserRegistration($admin, $token));

        Mail::assertSent(AdminInvitationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    });

    it('sets invitation_accepted_at to NULL', function () {
        $admin = Admin::factory()->create([
            'password' => 'test-token',
            'invitation_accepted_at' => null,
        ]);

        expect($admin->invitation_accepted_at)->toBeNull();
    });

    it('sets email_verified_at to NULL', function () {
        $admin = Admin::factory()->create([
            'password' => 'test-token',
            'email_verified_at' => null,
        ]);

        expect($admin->email_verified_at)->toBeNull();
    });

    it('sends email in English locale for en users', function () {
        Mail::fake();
        app()->setLocale('en');

        $project = Project::factory()->create();
        $token = base64_encode(random_bytes(32));

        $admin = Admin::factory()->create([
            'password' => $token,
        ]);
        $admin->projects()->attach($project);

        event(new UserRegistration($admin, $token));

        Mail::assertSent(AdminInvitationMail::class);
    });

    it('sends email in Spanish locale for es_UY users', function () {
        Mail::fake();
        app()->setLocale('es_UY');

        $project = Project::factory()->create();
        $token = base64_encode(random_bytes(32));

        $admin = Admin::factory()->create([
            'password' => $token,
        ]);
        $admin->projects()->attach($project);

        event(new UserRegistration($admin, $token));

        Mail::assertSent(AdminInvitationMail::class);
    });
});

describe('When a Member is created', function () {
    it('sends an invitation email', function () {
        Mail::fake();

        $project = Project::factory()->create();
        $family = Family::factory()->create(['project_id' => $project->id]);
        $token = base64_encode(random_bytes(32));

        $member = Member::factory()->create([
            'password' => $token,
            'invitation_accepted_at' => null,
            'email_verified_at' => null,
            'family_id' => $family->id,
        ]);

        event(new UserRegistration($member, $token));

        Mail::assertSent(MemberInvitationMail::class, function ($mail) use ($member) {
            return $mail->hasTo($member->email);
        });
    });

    it('sets invitation_accepted_at to NULL', function () {
        $family = Family::factory()->create();
        $member = Member::factory()->create([
            'password' => 'test-token',
            'invitation_accepted_at' => null,
            'family_id' => $family->id,
        ]);

        expect($member->invitation_accepted_at)->toBeNull();
    });

    it('sets email_verified_at to NULL', function () {
        $family = Family::factory()->create();
        $member = Member::factory()->create([
            'password' => 'test-token',
            'email_verified_at' => null,
            'family_id' => $family->id,
        ]);

        expect($member->email_verified_at)->toBeNull();
    });

    it('sends email in English locale for en users', function () {
        Mail::fake();
        app()->setLocale('en');

        $project = Project::factory()->create();
        $family = Family::factory()->create(['project_id' => $project->id]);
        $token = base64_encode(random_bytes(32));

        $member = Member::factory()->create([
            'password' => $token,
            'family_id' => $family->id,
        ]);

        event(new UserRegistration($member, $token));

        Mail::assertSent(MemberInvitationMail::class);
    });

    it('sends email in Spanish locale for es_UY users', function () {
        Mail::fake();
        app()->setLocale('es_UY');

        $project = Project::factory()->create();
        $family = Family::factory()->create(['project_id' => $project->id]);
        $token = base64_encode(random_bytes(32));

        $member = Member::factory()->create([
            'password' => $token,
            'family_id' => $family->id,
        ]);

        event(new UserRegistration($member, $token));

        Mail::assertSent(MemberInvitationMail::class);
    });
});
