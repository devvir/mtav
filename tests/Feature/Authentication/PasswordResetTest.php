<?php

/**
 * Tests for the password reset flow (forgot password + reset with token).
 */

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses()->group('Feature.Authentication');

describe('Requesting a password reset link', function () {
    it('renders the forgot-password page', function () {
        $response = $this->visitRoute('password.request', redirects: false);

        expect($response)->toBeOk()->toUsePage('Auth/ForgotPassword');
    });

    it('sends a reset link to an existing user', function () {
        Notification::fake();
        $user = User::find(102);

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $response->assertRedirect(route('login'));
    });

    it('does not reveal whether an email exists (same redirect, no notification)', function () {
        Notification::fake();

        $response = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        Notification::assertNothingSent();
        $response->assertRedirect(route('login'));
    });
});

describe('Resetting the password with a token', function () {
    it('renders the reset page with email and token props', function () {
        $response = $this->get(route('password.reset', ['token' => 'some-token', 'email' => 'a@b.com']));

        expect($response)->toBeOk()
            ->toUsePage('Auth/ResetPassword')
            ->toHaveProps(['token' => 'some-token', 'email' => 'a@b.com']);
    });

    it('resets the password given a valid token', function () {
        Notification::fake();
        $user = User::find(102);

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $response = $this->post(route('password.store'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));
        expect(Hash::check('brand-new-password', $user->fresh()->password))->toBeTrue();
    });

    it('rejects an invalid token', function () {
        $user = User::find(102);

        $response = $this->post(route('password.store'), [
            'token'                 => 'bogus-token',
            'email'                 => $user->email,
            'password'              => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertSessionHasErrors('email');
        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    });
});
