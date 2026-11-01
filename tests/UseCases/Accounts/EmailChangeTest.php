<?php

use App\Notifications\EmailChangeNotice;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Changing one's email: asking takes the password; the new address proves it is theirs by
 * receiving the link; the old one is told. It never tells who else has an account.
 */

beforeEach(function () {
    Notification::fake();
    $this->user = aUser(['email' => 'ana@example.com', 'firstname' => 'Ana', 'password' => 'the-Right-pass-1']);
});

function changeEmail($test, string|array $email, ?string $password = 'the-Right-pass-1')
{
    return $test->as($test->user)->patch(route('profile.update'), [
        'firstname' => 'Ana',
        'email'     => $email,
        ...($password ? ['current_password' => $password] : []),
    ]);
}

it('sends the confirmation to the new address, and word of it to the old one', function () {
    changeEmail($this, 'ana.new@example.com')->assertSessionHasNoErrors();

    Notification::assertSentTo($this->user, VerifyEmailNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routeNotificationFor('mail', $notification) === 'ana.new@example.com');
    Notification::assertSentTo($this->user, EmailChangeNotice::class, fn ($notification, $channels, $notifiable) => $notifiable->routeNotificationFor('mail', $notification) === 'ana@example.com');
    expect($this->user->fresh()->email)->toBe('ana@example.com');
});

it('asks for the password to change it, and sends nothing without the right one', function (?string $password) {
    changeEmail($this, 'ana.new@example.com', $password)->assertSessionHasErrors('current_password');

    Notification::assertNothingSent();
    expect($this->user->fresh()->new_email)->toBeNull();
})->with([null, 'not-it']);

it('changes nothing else when the password is wrong', function () {
    $this->as($this->user)->patch(route('profile.update'), [
        'firstname' => 'Another', 'email' => 'ana.new@example.com', 'current_password' => 'not-it',
    ])->assertSessionHasErrors('current_password');

    expect($this->user->fresh()->firstname)->toBe('Ana');
});

it('asks for no password to save the rest of the profile', function () {
    $this->as($this->user)->patch(route('profile.update'), ['firstname' => 'Anita', 'email' => 'ana@example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('flash.profile_updated'));

    expect($this->user->fresh()->firstname)->toBe('Anita');
});

it('reads the address in any case as the same one', function () {
    $this->as($this->user)->patch(route('profile.update'), ['firstname' => 'Ana', 'email' => 'ANA@example.com'])
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

it('refuses what is not an address', function () {
    changeEmail($this, ['ana@example.com'])->assertSessionHasErrors('email');
});

it('asks nothing, and sends nothing, for the change already waiting', function () {
    $this->user->update(['new_email' => 'ana.new@example.com']);

    changeEmail($this, 'ana.new@example.com', null)->assertSessionHasNoErrors();

    Notification::assertNothingSent();
    expect($this->user->fresh()->new_email)->toBe('ana.new@example.com');
});

it('drops the waiting change when the current address comes back, its link then useless', function () {
    changeEmail($this, 'ana.typo@example.com');
    $link = emailConfirmationLinkFor($this->user, 'ana.typo@example.com');

    $this->as($this->user)->patch(route('profile.update'), ['firstname' => 'Ana', 'email' => 'ana@example.com'])
        ->assertSessionHasNoErrors();

    expect($this->user->fresh()->new_email)->toBeNull();
    expect($this->as($this->user)->get($link))->toBeDenied();
    expect($this->user->fresh()->email)->toBe('ana@example.com');
});

it('never tells that another account has the address', function () {
    aUser(['email' => 'taken@example.com']);

    changeEmail($this, 'taken@example.com')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('info', __('flash.email_verification_sent', ['email' => 'taken@example.com']));
});

it('sends only a few a hour, answering in the form', function () {
    foreach (range(1, 5) as $i) {
        changeEmail($this, "ana{$i}@example.com")->assertSessionHasNoErrors();
    }

    changeEmail($this, 'ana6@example.com')->assertSessionHasErrors(['email' => __('validation.email_changes_limit')]);

    Notification::assertSentToTimes($this->user, VerifyEmailNotification::class, 5);
    expect($this->user->fresh()->new_email)->toBe('ana5@example.com');
});

it('takes only so many saves a minute', function () {
    foreach (range(1, 20) as $_) {
        $this->as($this->user)->patch(route('profile.update'), ['firstname' => 'Ana', 'email' => 'ana@example.com']);
    }

    $this->as($this->user)->patch(route('profile.update'), ['firstname' => 'Ana', 'email' => 'ana@example.com'])
        ->assertSessionHas('error', __('flash.too_many_attempts'));
});

describe('The link', function () {
    it('changes the address, once', function () {
        changeEmail($this, 'ana.new@example.com');
        $link = emailConfirmationLinkFor($this->user, 'ana.new@example.com');

        $this->as($this->user)->get($link)->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', __('flash.email_changed'));

        expect($this->user->fresh())->email->toBe('ana.new@example.com')->new_email->toBeNull();
    });

    it('works only for that account', function () {
        changeEmail($this, 'ana.new@example.com');

        expect($this->as(aUser())->get(emailConfirmationLinkFor($this->user, 'ana.new@example.com')))->toBeDenied();
        expect($this->user->fresh()->email)->toBe('ana@example.com');
    });

    it('says so, on the profile, when the address has an account by then', function () {
        changeEmail($this, 'ana.new@example.com');
        aUser(['email' => 'ana.new@example.com']);

        $this->as($this->user)->get(emailConfirmationLinkFor($this->user, 'ana.new@example.com'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('error', __('validation.email_taken'));

        expect($this->user->fresh())->email->toBe('ana@example.com')->new_email->toBeNull();
    });

    it('stops working once another address was asked for', function () {
        changeEmail($this, 'ana.first@example.com');
        $old = emailConfirmationLinkFor($this->user, 'ana.first@example.com');
        changeEmail($this, 'ana.second@example.com');

        expect($this->as($this->user)->get($old))->toBeDenied();
        expect($this->user->fresh()->email)->toBe('ana@example.com');
    });
});
