<?php

/**
 * Tests for the Profile settings page (ProfileController).
 *
 * Email changes are NOT applied directly: the new address is stored in
 * new_email and a verification notification is sent to it.
 */

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

uses()->group('Feature.Settings');

describe('Profile settings page', function () {
    it('loads for an authenticated user', function () {
        $response = $this->visitRoute('profile.edit', asMember: 102, redirects: false);

        expect($response)->toBeOk()->toUsePage('Settings/Profile');
    });

    it('redirects guests to the login page', function () {
        $response = $this->visitRoute('profile.edit', redirects: false);

        expect($response)->toRedirectTo('login');
    });
});

describe('Updating the profile', function () {
    it('updates profile fields (keeping the same email)', function () {
        $user = User::find(102);

        $response = $this->submitFormToRoute('profile.update', asMember: 102, redirects: false, data: [
            'firstname' => 'Updated',
            'lastname'  => 'Name',
            'phone'     => '099123456',
            'about'     => 'Hello there',
            'email'     => $user->email,
        ]);

        expect($response)->toRedirectTo('profile.edit');
        expect($user->fresh())
            ->firstname->toBe('Updated')
            ->lastname->toBe('Name')
            ->phone->toBe('099123456')
            ->about->toBe('Hello there');
    });

    it('does not change the email directly, but stores it and sends a verification', function () {
        Notification::fake();
        $user = User::find(102);
        $originalEmail = $user->email;

        $this->submitFormToRoute('profile.update', asMember: 102, redirects: false, data: [
            'firstname' => $user->firstname,
            'email'     => 'new-address@example.com',
        ]);

        expect($user->fresh())
            ->email->toBe($originalEmail)
            ->new_email->toBe('new-address@example.com');

        // The notification is sent to a transient User built with the NEW email
        Notification::assertSentTimes(VerifyEmailNotification::class, 1);
    });

    it('rejects an email already used by another user', function () {
        $user = User::find(102);
        $otherUser = User::find(105);

        $response = $this->submitFormToRoute('profile.update', asMember: 102, redirects: false, data: [
            'firstname' => $user->firstname,
            'email'     => $otherUser->email,
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('requires a firstname', function () {
        $user = User::find(102);

        $response = $this->submitFormToRoute('profile.update', asMember: 102, redirects: false, data: [
            'firstname' => '',
            'email'     => $user->email,
        ]);

        $response->assertSessionHasErrors('firstname');
    });
});
