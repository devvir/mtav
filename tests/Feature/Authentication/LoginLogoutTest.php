<?php

/**
 * Tests for the actual login (POST) and logout flows.
 *
 * All universe users share the password "password".
 */

use App\Models\User;

uses()->group('Feature.Authentication');

describe('Logging in', function () {
    it('authenticates a user with valid credentials', function () {
        $user = User::find(102);

        $response = $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    });

    it('rejects invalid credentials', function () {
        $user = User::find(102);

        $response = $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    });

    it('rejects unknown emails', function () {
        $response = $this->post(route('login'), [
            'email'    => 'nobody@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    });
});

describe('Logging out', function () {
    it('logs the user out and redirects to the login page', function () {
        $this->actingAs(User::find(102));

        $response = $this->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    });
});
