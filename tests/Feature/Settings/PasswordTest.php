<?php

/**
 * Tests for the Password settings page (PasswordController).
 *
 * All universe users share the password "password".
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses()->group('Feature.Settings');

describe('Password settings page', function () {
    it('loads for an authenticated user', function () {
        $response = $this->visitRoute('password.edit', asMember: 102, redirects: false);

        expect($response)->toBeOk()->toUsePage('Settings/Password');
    });

    it('redirects guests to the login page', function () {
        $response = $this->visitRoute('password.edit', redirects: false);

        expect($response)->toRedirectTo('login');
    });
});

describe('Updating the password', function () {
    it('updates the password when the current password is correct', function () {
        $this->actingAs(User::find(102));

        $this->put(route('password.update'), [
            'current_password'      => 'password',
            'password'              => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertSessionHasNoErrors();

        expect(Hash::check('new-secret-password', User::find(102)->password))->toBeTrue();
    });

    it('rejects an incorrect current password', function () {
        $this->actingAs(User::find(102));

        $this->put(route('password.update'), [
            'current_password'      => 'wrong-password',
            'password'              => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertSessionHasErrors('current_password');

        expect(Hash::check('password', User::find(102)->password))->toBeTrue();
    });

    it('rejects a password confirmation mismatch', function () {
        $this->actingAs(User::find(102));

        $this->put(route('password.update'), [
            'current_password'      => 'password',
            'password'              => 'new-secret-password',
            'password_confirmation' => 'something-else',
        ])->assertSessionHasErrors('password');
    });
});
