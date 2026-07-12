<?php

use App\Events\UserRegistration;
use Illuminate\Support\Facades\Event;

/**
 * Browser (E2E) journey helpers.
 *
 * The pest browser plugin serves the app in-process, so tests can freely mix
 * browser actions with Eloquent state and event listeners.
 */

/**
 * Capture the invitation URL produced while running the given action.
 *
 * The plaintext token only exists in the UserRegistration event (the DB
 * stores it hashed as the user's password), so we listen for the event
 * while the browser action triggers an invite.
 */
function captureInvitationUrl(callable $act): string
{
    $url = null;

    Event::listen(UserRegistration::class, function (UserRegistration $event) use (&$url) {
        $url = route('invitation.edit', [
            'email' => $event->user->email,
            'token' => $event->token,
        ], absolute: false);
    });

    $act();

    expect($url)->not->toBeNull('Expected an invitation to be sent during the action, but none was.');

    return $url;
}
