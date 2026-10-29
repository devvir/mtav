<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

/**
 * Signing in with Google: the linked account, else the one with that email (Google vouches
 * for it), else a new one; never on an email Google has not verified. Offered only where
 * Google is set up.
 */

beforeEach(function () {
    config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
});

/** Google answers for this person */
function googleSays(array $user): void
{
    $google = (new GoogleUser())->setRaw($user)->map([
        'id'    => $user['sub'],
        'name'  => trim(($user['given_name'] ?? '') . ' ' . ($user['family_name'] ?? '')),
        'email' => $user['email'],
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($google);
}

it('signs in the account with that email, and links it', function () {
    $ana = aUser(['email' => 'ana@example.com']);
    googleSays(['sub' => 'g-1', 'email' => 'ana@example.com', 'email_verified' => true]);

    $this->get(route('google.callback'))->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($ana);
    expect($ana->fresh()->google_id)->toBe('g-1');
});

it('opens a new account, its email already confirmed', function () {
    googleSays(['sub' => 'g-2', 'email' => 'luis@example.com', 'email_verified' => true, 'given_name' => 'Luis', 'family_name' => 'Pérez']);

    $this->get(route('google.callback'));

    $luis = User::firstWhere('email', 'luis@example.com');
    expect($luis)->firstname->toBe('Luis')->lastname->toBe('Pérez')->google_id->toBe('g-2')
        ->and($luis->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($luis);
});

it('never opens an account on an email Google has not verified', function () {
    aUser(['email' => 'ana@example.com']);
    googleSays(['sub' => 'g-3', 'email' => 'ana@example.com', 'email_verified' => false]);

    $this->get(route('google.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
});

it('is not offered where Google is not set up', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('google.redirect'))->assertNotFound();
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('google', false));
});
