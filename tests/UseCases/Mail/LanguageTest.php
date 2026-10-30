<?php

use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\App;

/**
 * An email is written whole in its reader's language: the mail template's own words
 * (greeting, sign-off, the link to copy, the footer) as much as ours.
 */
it('writes the whole email in the reader\'s language, the template\'s own words too', function () {
    $user = aUser(['locale' => 'es_UY']);
    App::setLocale('es_UY');

    $html = (string) (new VerifyEmailNotification())->toMail($user)->render();

    expect($html)->toContain('Saludos,')->toContain('Todos los derechos reservados.')
        ->not->toContain('Regards')->not->toContain('having trouble')->not->toContain('All rights reserved');
});
