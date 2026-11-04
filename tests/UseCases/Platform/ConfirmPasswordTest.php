<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * The console's gravest actions ask for the password once more: the superadmin role, deleting an
 * account or a project for good, deploying. An account opened with Google has no password it
 * knows: it emails itself a link to choose one.
 */

beforeEach(function () {
    config(['delivery.deployers' => ['root@example.com'], 'delivery.github.token' => 'token']);
    $this->superadmin = aUser(['email' => 'root@example.com', 'password' => 'the-Right-pass-1', 'is_superadmin' => true]);
    $this->other = aUser(['email' => 'other@example.com']);
    $this->as($this->superadmin);
});

dataset('grave actions', [
    'inviting a support account' => [fn () => test()->post(route('admin.superadmins.store'), ['email' => 'new.support@example.com'])],
    'removing the role'          => [fn () => test()->put(route('admin.users.superadmin', test()->other), ['grant' => false])],
    'deleting an account'        => [fn () => test()->delete(route('admin.users.destroy', test()->other))],
    'deleting a project'         => [fn () => test()->delete(route('admin.projects.destroy', aProject(['name' => 'Gone'])), ['confirmation' => 'Gone'])],
    'deploying dev'              => [fn () => test()->post(route('admin.deployments.dev'), [])],
    'deploying production'       => [fn () => test()->post(route('admin.deployments.production'), [])],
    'rolling back'               => [fn () => test()->post(route('admin.deployments.rollback'), [])],
    'starting over'              => [fn () => test()->post(route('admin.deployments.fresh'), [])],
]);

it('asks for the password first, doing nothing', function (Closure $action) {
    Http::fake();

    $action()->assertRedirect(route('password.confirm'));

    Http::assertNothingSent();
    expect(User::find($this->other->id)->isSuperadmin())->toBeFalse();
})->with('grave actions');

it('does not take the role away while asking', function () {
    $this->other->forceFill(['is_superadmin' => true])->save();

    $this->put(route('admin.users.superadmin', $this->other), ['grant' => false])->assertRedirect(route('password.confirm'));

    expect($this->other->fresh()->isSuperadmin())->toBeTrue();
});

it('leaves the account and the project in place when asking', function () {
    $project = aProject(['name' => 'Kept']);

    $this->delete(route('admin.users.destroy', $this->other))->assertRedirect(route('password.confirm'));
    $this->delete(route('admin.projects.destroy', $project), ['confirmation' => 'Kept'])->assertRedirect(route('password.confirm'));

    expect($this->other->fresh())->not->toBeNull()
        ->and($project->fresh())->not->toBeNull();
});

it('does it once the password was given, for a while', function () {
    $this->post(route('admin.superadmins.store'), ['email' => 'new.support@example.com'])->assertRedirect(route('password.confirm'));

    $this->post(route('password.confirm'), ['password' => 'the-Right-pass-1'])->assertSessionHasNoErrors();

    $this->post(route('admin.superadmins.store'), ['email' => 'new.support@example.com'])->assertSessionHasNoErrors();
    expect(App\Models\SupportInvitation::where('email', 'new.support@example.com')->exists())->toBeTrue();
    $this->other->forceFill(['is_superadmin' => true])->save();

    $this->put(route('admin.users.superadmin', $this->other), ['grant' => false])->assertSessionHasNoErrors();
    expect($this->other->fresh()->isSuperadmin())->toBeFalse();
});

it('returns them to the page they were on', function () {
    $this->from(route('admin.superadmins.index'))->post(route('admin.superadmins.store'), ['email' => 'new.support@example.com']);

    $this->post(route('password.confirm'), ['password' => 'the-Right-pass-1'])
        ->assertRedirect(route('admin.superadmins.index'))
        ->assertSessionHas('info', __('flash.password_confirmed'));
});

it('asks again once the while is over', function () {
    $this->withSession(['auth.password_confirmed_at' => time() - config('auth.password_timeout') - 1]);

    $this->post(route('admin.superadmins.store'), ['email' => $this->other->email])->assertRedirect(route('password.confirm'));
});

it('refuses a wrong password, and goes on asking', function () {
    $this->post(route('password.confirm'), ['password' => 'a-wrong-one'])->assertSessionHasErrors('password');

    $this->post(route('admin.superadmins.store'), ['email' => $this->other->email])->assertRedirect(route('password.confirm'));
    expect($this->other->fresh()->isSuperadmin())->toBeFalse();
});

it('shows the page only to someone signed in', function () {
    $this->get(route('password.confirm'))->assertInertia(fn ($page) => $page->component('Auth/ConfirmPassword'));

    auth()->logout();

    $this->get(route('password.confirm'))->assertRedirect(route('login'));
});

it('leaves what is not so grave alone', function () {
    $person = aUser();

    $this->put(route('admin.users.block', $person), ['block' => true])->assertSessionHasNoErrors();

    expect($person->fresh()->isBlocked())->toBeTrue();
});

it('lets an account opened with Google choose a password by email, and go on with it', function () {
    Notification::fake();
    $google = aUser(['email' => 'google@example.com', 'is_superadmin' => true]);
    $google->forceFill(['google_id' => 'g-1', 'password' => Str::password(40)])->save();
    $this->as($google);

    // It knows no password: nothing it can type lets it through
    $this->post(route('admin.superadmins.store'), ['email' => $this->other->email])->assertRedirect(route('password.confirm'));
    $this->post(route('password.confirm'), ['password' => 'a-guess'])->assertSessionHasErrors('password');

    // The link comes to its inbox
    $this->post(route('password.link'))->assertSessionHas('info', __('passwords.sent'));
    Notification::assertSentTo($google, ResetPasswordNotification::class);
    $token = Password::createToken($google->fresh());

    $this->post(route('password.store'), [
        'token' => $token, 'email' => $google->email, 'password' => 'a-Chosen-pass-1', 'password_confirmation' => 'a-Chosen-pass-1',
    ])->assertSessionHasNoErrors();

    // A new password ends its sessions: signed in again, it gives that one, and goes on
    $this->as($google->fresh());
    $this->post(route('password.confirm'), ['password' => 'a-Chosen-pass-1'])->assertSessionHasNoErrors();
    $this->post(route('admin.superadmins.store'), ['email' => 'new.support@example.com'])->assertSessionHasNoErrors();

    expect(App\Models\SupportInvitation::where('email', 'new.support@example.com')->exists())->toBeTrue();
});
