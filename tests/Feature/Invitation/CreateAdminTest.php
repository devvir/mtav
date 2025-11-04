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

use App\Mail\AdminInvitationMail;
use App\Models\Admin;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

uses()->group('Feature.Invitation');

beforeEach(function () {
    Mail::fake();
});

describe('When creating an Admin', function () {
    describe('Authorization', function () {
        it('allows admin to create admins for projects they manage', function () {
            $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            expect(Admin::where('email', 'newadmin@example.com'))->toExist();
        });

        it('prevents members from creating admins', function () {
            $response = $this->sendPostRequest('admins.store', asMember: 102, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ], redirects: false);

            expect($response)->toRedirectTo('home');
        });

        it('prevents guests from creating admins', function () {
            $response = $this->sendPostRequest('admins.store', redirects: false, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            $response->assertRedirect(route('login'));
        });
    });

    describe('Project assignment validation', function () {
        it('requires at least one project', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [],
            ]);

            expect(inertiaErrors($response))->toHaveKey('project_ids');
        });

        it('rejects admin assigning projects they do not manage', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1, 2], // Project 2 not managed by admin
            ]);

            expect(inertiaErrors($response))->toHaveKey('project_ids');
        });

        it('allows admin with multiple projects to assign multiple projects', function () {
            $this->sendPostRequest('admins.store', asAdmin: 12, data: [
                'email' => 'multiproject@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [2, 3],
            ]);

            $newAdmin = Admin::where('email', 'multiproject@example.com')->first()->fresh();
            expect($newAdmin->projects->pluck('id')->sort()->values()->toArray())->toBe([2, 3]);
        });

        it('rejects non-existent project ids', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [99999],
            ]);

            expect(inertiaErrors($response))->toHaveKey('project_ids.0');
        });
    });

    describe('Email validation', function () {
        it('requires email', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            expect(inertiaErrors($response))->toHaveKey('email');
        });

        it('requires valid email format', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'not-an-email',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            expect(inertiaErrors($response))->toHaveKey('email');
        });

        it('requires unique email', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'admin12@example.com', // Already exists (Admin #12)
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            expect(inertiaErrors($response))->toHaveKey('email');
        });
    });

    describe('Name validation', function () {
        it('requires firstname', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            expect(inertiaErrors($response))->toHaveKey('firstname');
        });

        it('requires firstname to be at least 2 characters', function () {
            $response = $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'J',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            expect(inertiaErrors($response))->toHaveKey('firstname');
        });

        it('allows optional lastname', function () {
            $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'project_ids' => [1],
            ]);

            expect(Admin::where('email', 'newadmin@example.com'))->toExist();
        });
    });

    describe('Invitation creation', function () {
        it('creates admin with invitation fields set correctly', function () {
            $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'newadmin@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            $newAdmin = User::where('email', 'newadmin@example.com')->first();
            expect($newAdmin)->not->toBeNull()
                ->and($newAdmin->invitation_accepted_at)->toBeNull()
                ->and($newAdmin->email_verified_at)->toBeNull();

            expect($newAdmin)->toBeAdmin();
        });

        it('sends invitation email upon creation', function () {
            $this->sendPostRequest('admins.store', asAdmin: 11, data: [
                'email' => 'adminemail@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'project_ids' => [1],
            ]);

            Mail::assertSent(AdminInvitationMail::class, function ($mail) {
                return $mail->hasTo('adminemail@example.com');
            });
        });
    });
});
