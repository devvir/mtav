<?php

use App\Notifications\EmailChangeNotice;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Changing one's email: the new address proves it is theirs by receiving the link; the
 * old one is told.
 */

it('sends the confirmation to the new address, and word of it to the old one', function () {
    Notification::fake();
    $user = aUser(['email' => 'ana@example.com', 'firstname' => 'Ana']);

    $this->as($user)->patch(route('profile.update'), ['firstname' => 'Ana', 'email' => 'ana.new@example.com'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, VerifyEmailNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routeNotificationFor('mail', $notification) === 'ana.new@example.com');
    Notification::assertSentTo($user, EmailChangeNotice::class, fn ($notification, $channels, $notifiable) => $notifiable->routeNotificationFor('mail', $notification) === 'ana@example.com');
    expect($user->fresh()->email)->toBe('ana@example.com');
});
