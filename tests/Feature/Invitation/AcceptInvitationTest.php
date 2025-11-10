<?php

/**
 * Tests for the invitation acceptance form and submission.
 *
 * Covers:
 * - Required and optional fields
 * - Password setting
 * - Successful submission results
 * - Redirect behavior after completion
 */

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses()->group('Feature.Invitation');

beforeEach(function () {
    Storage::fake('public');
});

describe('When submitting the invitation acceptance form', function () {
    it('requires a password', function () {
        /** @var \Illuminate\Testing\TestResponse $response */
        $response = $this->submitFormToRoute('invitation.update', asMember: 148, data: [
            // password missing
        ], redirects: false);

        $response->assertInvalid('password');
    });

    it('requires password confirmation to match', function () {
        $response = $this->submitFormToRoute('invitation.update', asMember: 148, redirects: false, data: [
            'password'              => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertInvalid('password');
    });

    it('accepts optional fields: firstname, lastname, phone, legal_id', function () {
        $this->submitFormToRoute('invitation.update', asMember: 148, data: [
            'password'              => 'secure-password',
            'password_confirmation' => 'secure-password',
            'firstname'             => 'John',
            'lastname'              => 'Doe',
            'phone'                 => '+1234567890',
            'legal_id'              => 'ABC123',
        ]);

        $member = Member::find(148);
        expect($member->firstname)->toBe('John')
            ->and($member->lastname)->toBe('Doe')
            ->and($member->phone)->toBe('+1234567890')
            ->and($member->legal_id)->toBe('ABC123');
    });

    it('accepts avatar upload (image, max 2MB)', function () {
        $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100)->size(1024);

        $this->submitFormToRoute('invitation.update', asMember: 148, data: [
            'password'              => 'secure-password',
            'password_confirmation' => 'secure-password',
            'avatar'                => $avatar,
        ]);

        $member = Member::find(148);
        expect($member->avatar)->not->toBeNull();
        expect(Storage::disk('public')->exists($member->avatar))->toBeTrue();
    });

    describe('on success', function () {
        it('sets invitation_accepted_at to current datetime', function () {
            $this->submitFormToRoute('invitation.update', asMember: 148, data: [
                'password'              => 'secure-password',
                'password_confirmation' => 'secure-password',
            ]);

            $member = Member::find(148);
            expect($member->invitation_accepted_at)->not->toBeNull();
        });

        it('sets email_verified_at to current datetime', function () {
            $this->submitFormToRoute('invitation.update', asMember: 148, data: [
                'password'              => 'secure-password',
                'password_confirmation' => 'secure-password',
            ]);

            $member = Member::find(148);
            expect($member->email_verified_at)->not->toBeNull();
        });

        it('updates the password', function () {
            $this->submitFormToRoute('invitation.update', asMember: 148, data: [
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

            $member = Member::find(148);
            expect(Hash::check('new-secure-password', $member->password))->toBeTrue();
        });

        it('redirects to Dashboard with success message', function () {
            $response = $this->submitFormToRoute('invitation.update', asMember: 148, data: [
                'password'              => 'secure-password',
                'password_confirmation' => 'secure-password',
            ], redirects: false);

            expect($response)->toRedirectTo('dashboard');
            expect($response->getSession()->get('success'))->not->toBeNull();
        });

        it('works for Admin users as well', function () {
            $this->submitFormToRoute('invitation.update', asAdmin: 18, data: [
                'password'              => 'admin-password',
                'password_confirmation' => 'admin-password',
                'firstname'             => 'Sarah',
                'lastname'              => 'Admin',
            ], redirects: false);

            $admin = Admin::find(18);
            expect($admin->invitation_accepted_at)->not->toBeNull()
                ->and($admin->email_verified_at)->not->toBeNull()
                ->and($admin->firstname)->toBe('Sarah')
                ->and($admin->lastname)->toBe('Admin')
                ->and(Hash::check('admin-password', $admin->password))->toBeTrue();
        });
    });
});
