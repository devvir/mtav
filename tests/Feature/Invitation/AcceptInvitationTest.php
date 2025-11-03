<?php

/**
 * Tests for the invitation acceptance form and submission.
 *
 * Covers:
 * - Form display for Members vs Admins
 * - Required and optional fields
 * - Password setting
 * - Successful submission results
 * - Redirect behavior after completion
 */

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses()->group('Feature.Invitation');

beforeEach(function () {
    Storage::fake('public');
});

describe('When submitting the invitation acceptance form', function () {
    it('requires a password', function () {
        $family = Family::factory()->create();
        $token = base64_encode(random_bytes(32));
        $member = Member::factory()->create([
            'password' => $token,
            'family_id' => $family->id,
            'invitation_accepted_at' => null,
            'email_verified_at' => null,
        ]);

        // Simulate the invitation flow: authenticate with email+token
        $this->post(route('invitation.show'), [
            'email' => $member->email,
            'token' => $token,
        ]);

        // Now submit the form without password
        $this->post(route('invitation.store'), [
            'email' => $member->email,
            'token' => $token,
            // password missing
        ])->assertSessionHasErrors('password');
    });

    it('requires password confirmation to match', function () {
        $family = Family::factory()->create();
        $token = base64_encode(random_bytes(32));
        $member = Member::factory()->create([
            'password' => $token,
            'family_id' => $family->id,
            'invitation_accepted_at' => null,
            'email_verified_at' => null,
        ]);

        // Simulate the invitation flow: authenticate with email+token
        $this->post(route('invitation.show'), [
            'email' => $member->email,
            'token' => $token,
        ]);

        // Submit with mismatched passwords
        $this->post(route('invitation.store'), [
            'email' => $member->email,
            'token' => $token,
            'password' => 'password123',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    });

    it('accepts optional fields: firstname, lastname, phone, legal_id', function () {
        $family = Family::factory()->create();
        $token = base64_encode(random_bytes(32));
        $member = Member::factory()->create([
            'password' => $token,
            'family_id' => $family->id,
            'invitation_accepted_at' => null,
            'email_verified_at' => null,
            'firstname' => null,
            'lastname' => null,
            'phone' => null,
            'legal_id' => null,
        ]);

        // Simulate the invitation flow: authenticate with email+token
        $this->post(route('invitation.show'), [
            'email' => $member->email,
            'token' => $token,
        ]);

        // Submit with optional fields
        $this->post(route('invitation.store'), [
            'email' => $member->email,
            'token' => $token,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'phone' => '+1234567890',
            'legal_id' => 'ABC123',
        ])->assertRedirect(route('home'));

        $member->refresh();
        expect($member->firstname)->toBe('John')
            ->and($member->lastname)->toBe('Doe')
            ->and($member->phone)->toBe('+1234567890')
            ->and($member->legal_id)->toBe('ABC123');
    });

    it('accepts avatar upload (image, max 2MB)', function () {
        $family = Family::factory()->create();
        $token = base64_encode(random_bytes(32));
        $member = Member::factory()->create([
            'password' => $token,
            'family_id' => $family->id,
            'invitation_accepted_at' => null,
            'email_verified_at' => null,
        ]);

        $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100)->size(1024);

        // Simulate the invitation flow: authenticate with email+token
        $this->post(route('invitation.show'), [
            'email' => $member->email,
            'token' => $token,
        ]);

        // Submit with avatar
        $this->post(route('invitation.store'), [
            'email' => $member->email,
            'token' => $token,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'avatar' => $avatar,
        ])->assertRedirect(route('home'));

        $member->refresh();
        expect($member->avatar)->not->toBeNull();
        expect(Storage::disk('public')->exists($member->avatar))->toBeTrue();
    });

    describe('on success', function () {
        it('sets invitation_accepted_at to current datetime', function () {
            $family = Family::factory()->create();
            $token = base64_encode(random_bytes(32));
            $member = Member::factory()->create([
                'password' => $token,
                'family_id' => $family->id,
                'invitation_accepted_at' => null,
                'email_verified_at' => null,
            ]);

            // Simulate the invitation flow: authenticate with email+token
            $this->post(route('invitation.show'), [
                'email' => $member->email,
                'token' => $token,
            ]);

            // Submit the form
            $this->post(route('invitation.store'), [
                'email' => $member->email,
                'token' => $token,
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ])->assertRedirect(route('home'));

            $member->refresh();
            expect($member->invitation_accepted_at)->not->toBeNull();
        });

        it('sets email_verified_at to current datetime', function () {
            $family = Family::factory()->create();
            $token = base64_encode(random_bytes(32));
            $member = Member::factory()->create([
                'password' => $token,
                'family_id' => $family->id,
                'invitation_accepted_at' => null,
                'email_verified_at' => null,
            ]);

            // Simulate the invitation flow: authenticate with email+token
            $this->post(route('invitation.show'), [
                'email' => $member->email,
                'token' => $token,
            ]);

            // Submit the form
            $this->post(route('invitation.store'), [
                'email' => $member->email,
                'token' => $token,
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ])->assertRedirect(route('home'));

            $member->refresh();
            expect($member->email_verified_at)->not->toBeNull();
        });

        it('updates the password', function () {
            $family = Family::factory()->create();
            $token = base64_encode(random_bytes(32));
            $member = Member::factory()->create([
                'password' => $token,
                'family_id' => $family->id,
                'invitation_accepted_at' => null,
                'email_verified_at' => null,
            ]);

            // Simulate the invitation flow: authenticate with email+token
            $this->post(route('invitation.show'), [
                'email' => $member->email,
                'token' => $token,
            ]);

            // Submit with new password
            $this->post(route('invitation.store'), [
                'email' => $member->email,
                'token' => $token,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])->assertRedirect(route('home'));

            $member->refresh();
            expect(Hash::check('new-secure-password', $member->password))->toBeTrue();
        });

        it('redirects to homepage with success message', function () {
            $family = Family::factory()->create();
            $token = base64_encode(random_bytes(32));
            $member = Member::factory()->create([
                'password' => $token,
                'family_id' => $family->id,
                'invitation_accepted_at' => null,
                'email_verified_at' => null,
            ]);

            // Simulate the invitation flow: authenticate with email+token
            $this->post(route('invitation.show'), [
                'email' => $member->email,
                'token' => $token,
            ]);

            // Submit the form
            $this->post(route('invitation.store'), [
                'email' => $member->email,
                'token' => $token,
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ])->assertRedirect(route('home'))
              ->assertSessionHas('success');
        });
    });
});
