<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/**
 * A new password closes every other way in: sessions elsewhere, and remembered devices.
 * The reset page never tells who has an account.
 */

function aSessionOf(User $user, string $id = 'a-session-elsewhere'): string
{
    DB::table('sessions')->insert([
        'id'            => $id,
        'user_id'       => $user->id,
        'payload'       => '',
        'last_activity' => now()->timestamp,
    ]);

    return $id;
}

function sessionsOf(User $user): array
{
    return DB::table('sessions')->where('user_id', $user->id)->pluck('id')->all();
}

function resetPassword($test, User $user, ?string $token = null, ?string $email = null)
{
    return $test->post(route('password.store'), [
        'token'                 => $token ?? Password::createToken($user),
        'email'                 => $email ?? $user->email,
        'password'              => 'a-New-pass-123',
        'password_confirmation' => 'a-New-pass-123',
    ]);
}

function changePassword($test, string $current = 'the-Old-pass-1')
{
    return $test->put(route('password.update'), [
        'current_password'      => $current,
        'password'              => 'a-New-pass-123',
        'password_confirmation' => 'a-New-pass-123',
    ]);
}

describe('Resetting a forgotten password', function () {
    beforeEach(function () {
        $this->user = aUser(['email' => 'ana@example.com', 'remember_token' => 'the-old-remember-token']);
    });

    it('ends every session of that account, and forgets its remembered devices', function () {
        aSessionOf($this->user, 'one');
        aSessionOf($this->user, 'two');
        aSessionOf(aUser(), 'someone-elses');

        resetPassword($this, $this->user)->assertRedirect(route('login'))->assertSessionHasNoErrors();

        expect(sessionsOf($this->user))->toBe([])
            ->and($this->user->fresh()->remember_token)->not->toBe('the-old-remember-token')
            ->and(Hash::check('a-New-pass-123', $this->user->fresh()->password))->toBeTrue()
            ->and(DB::table('sessions')->where('id', 'someone-elses')->exists())->toBeTrue();
    });

    it('ends nothing when the link is not good', function () {
        aSessionOf($this->user);

        resetPassword($this, $this->user, token: 'not-the-token')->assertSessionHasErrors('email');

        expect(sessionsOf($this->user))->toHaveCount(1)
            ->and($this->user->fresh()->remember_token)->toBe('the-old-remember-token');
    });

    it('answers the same for an address with no account as for a bad link', function () {
        resetPassword($this, $this->user, token: 'not-the-token')
            ->assertSessionHasErrors(['email' => __('passwords.token')]);

        session()->flush();
        resetPassword($this, $this->user, token: 'not-the-token', email: 'nobody@example.com')
            ->assertSessionHasErrors(['email' => __('passwords.token')]);
    });

    it('takes only a few tries a minute', function () {
        foreach (range(1, 6) as $_) {
            resetPassword($this, $this->user, token: 'not-the-token')->assertSessionHasErrors('email');
        }

        session()->flush();
        resetPassword($this, $this->user, token: 'not-the-token')
            ->assertRedirect()
            ->assertSessionHas('error', __('flash.too_many_attempts'));
    });

    it('opens from a browser signed in, and that browser signs in again too', function () {
        $token = Password::createToken($this->user);

        $this->as($this->user)->get(route('password.reset', ['token' => $token, 'email' => $this->user->email]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ResetPassword'));

        resetPassword($this, $this->user, token: $token)->assertRedirect(route('login'));

        $this->assertGuest();
    });
});

describe('Changing the password in the profile', function () {
    beforeEach(function () {
        $this->user = aUser(['password' => 'the-Old-pass-1', 'remember_token' => 'the-old-remember-token']);
    });

    it('ends the other sessions and keeps this one', function () {
        aSessionOf($this->user, 'elsewhere');
        $here = aSessionOf($this->user, str_repeat('a', 40));
        aSessionOf(aUser(), 'someone-elses');

        changePassword($this->as($this->user)->withCookie(config('session.cookie'), $here))
            ->assertSessionHasNoErrors();

        expect(sessionsOf($this->user))->toBe([$here])
            ->and(DB::table('sessions')->where('id', 'someone-elses')->exists())->toBeTrue()
            ->and(Hash::check('a-New-pass-123', $this->user->fresh()->password))->toBeTrue();
        $this->assertAuthenticatedAs($this->user);
    });

    it('forgets remembered devices, this browser going on with a fresh token', function () {
        $recaller = Auth::guard()->getRecallerName();

        $response = changePassword($this->as($this->user)->withCookie($recaller, "{$this->user->id}|the-old-remember-token|x"))
            ->assertSessionHasNoErrors();

        $token = $this->user->fresh()->remember_token;
        expect($token)->not->toBe('the-old-remember-token');

        expect($response->getCookie($recaller)->getValue())->toContain("{$this->user->id}|{$token}|");
    });

    it('asks nothing of a browser not remembered: it gets no such cookie', function () {
        changePassword($this->as($this->user))->assertCookieMissing(Auth::guard()->getRecallerName());
    });

    it('changes nothing with a wrong current password', function () {
        aSessionOf($this->user);

        changePassword($this->as($this->user), 'not-it')->assertSessionHasErrors('current_password');

        expect(sessionsOf($this->user))->toHaveCount(1)
            ->and($this->user->fresh()->remember_token)->toBe('the-old-remember-token')
            ->and(Hash::check('the-Old-pass-1', $this->user->fresh()->password))->toBeTrue();
    });

    it('takes only a few tries a minute', function () {
        foreach (range(1, 10) as $_) {
            changePassword($this->as($this->user), 'not-it');
        }

        session()->flush();
        changePassword($this->as($this->user), 'not-it')
            ->assertRedirect()
            ->assertSessionHas('error', __('flash.too_many_attempts'));
    });
});

describe('A link to choose a password, from the profile', function () {
    it('goes to the inbox of the account, whatever was asked', function () {
        Notification::fake();
        $user = aUser(['email' => 'ana@example.com']);

        $this->as($user)->post(route('password.link'), ['email' => 'someone@else.com'])
            ->assertRedirect()
            ->assertSessionHas('info', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        Notification::assertCount(1);
    });

    it('gives a password to an account opened with Google, which never had one', function () {
        Notification::fake();
        $user = aUser(['email' => 'ana@example.com', 'password' => Str::password(40), 'google_id' => 'google-1']);
        $this->as($user)->post(route('password.link'));

        resetPassword($this, $user, token: urldecode(Str::after(Str::before(passwordResetLinkFor($user), '?'), '/reset-password/')))
            ->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => 'ana@example.com', 'password' => 'a-New-pass-123']);
        $this->assertAuthenticatedAs($user);
    });

    it('is refused to someone not signed in, and limited for the rest', function () {
        $this->post(route('password.link'))->assertRedirect(route('login'));

        Notification::fake();
        $user = aUser();
        foreach (range(1, 3) as $_) {
            $this->as($user)->post(route('password.link'));
        }

        $this->as($user)->post(route('password.link'))
            ->assertSessionHas('error', __('flash.too_many_attempts'));
    });
});
