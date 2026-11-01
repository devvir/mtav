<?php

use App\Models\Activity;

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

describe('A blocked account, with the right password', function () {
    beforeEach(fn () => $this->user->forceFill(['blocked_at' => now()])->save());

    it('is answered as a wrong password would be', function () {
        signIn($this, 'the-Right-pass-1')->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    });

    it('is never signed in, not even in the record of who was', function () {
        signIn($this, 'the-Right-pass-1');

        expect(Activity::where('action', 'signed_in')->count())->toBe(0)
            ->and(Activity::where('action', 'signed_out')->count())->toBe(0)
            ->and(Activity::where('action', 'sign_in_failed')->count())->toBe(1);
    });

    it('counts as a failed try, so that guessing stays slowed down', function () {
        foreach (range(1, 5) as $_) {
            signIn($this, 'the-Right-pass-1');
        }

        signIn($this, 'the-Right-pass-1')->assertSessionHasErrors('email');

        expect(session('errors')->first('email'))->toStartWith('Too many login attempts');
    });
});

it('answers on the page, not with a bare error, when asked too often', function () {
    foreach (range(1, 10) as $_) {
        signIn($this, 'a-wrong-one');
    }

    session()->flush();
    $this->from(route('login'));
    signIn($this, 'a-wrong-one')
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', __('flash.too_many_attempts'));
});
