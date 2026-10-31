<?php

/**
 * Signing in: the right password lets people in; guessing is slowed down.
 */

beforeEach(function () {
    $this->user = aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);
});

function signIn($test, string $password)
{
    return $test->post(route('login'), ['email' => 'ana@example.com', 'password' => $password]);
}

it('signs in with the right password', function () {
    signIn($this, 'the-Right-pass-1')->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($this->user);
});

it('locks signing in for a while after five wrong passwords, even with the right one', function () {
    foreach (range(1, 5) as $_) {
        signIn($this, 'a-wrong-one')->assertSessionHasErrors('email');
    }

    signIn($this, 'the-Right-pass-1')->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toStartWith('Too many login attempts');

    $this->assertGuest();
});

it('leaves someone who signs out on the landing page, with no offer to sign in again', function () {
    $this->as(aUser())->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
    $this->get(route('home'))->assertInertia(fn ($page) => $page->component('Welcome'));
});
