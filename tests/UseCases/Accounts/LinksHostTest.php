<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * What a request says about its host never decides where a mailed link leads: anyone can
 * ask for someone else's reset link, saying anything.
 */

beforeEach(function () {
    aUser(['email' => 'owner@example.com']);
});

/** The reset link mailed in answer to a request to this address, with these headers */
function resetLinkAsked(string $root, array $headers = []): string
{
    test()->withHeaders($headers)->post("{$root}/forgot-password", ['email' => 'owner@example.com'])->assertSessionHasNoErrors();

    $mail = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    preg_match('~href="([^"]*/reset-password/[^"]*)"~', $mail->getHtmlBody(), $link);

    return html_entity_decode($link[1]);
}

it('takes no forwarded host or port for the links it mails', function () {
    $link = resetLinkAsked('http://localhost', ['X-Forwarded-Host' => 'evil.example', 'X-Forwarded-Port' => '1337']);

    expect($link)->toStartWith('http://localhost/reset-password/');
});

describe('In a deployment', function () {
    beforeEach(function () {
        config(['app.deployment' => 'production', 'app.url' => 'https://www.mtav.uy']);
    });

    it('sends the link to its own address when a request names another host', function () {
        $link = resetLinkAsked('http://evil.example', ['X-Forwarded-Proto' => 'https']);

        expect($link)->toStartWith('https://www.mtav.uy/reset-password/');
    });

    it('holds with a port on the host named, and with a forwarded host too', function () {
        $link = resetLinkAsked('http://evil.example:8443', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'worse.example']);

        expect($link)->toStartWith('https://www.mtav.uy/reset-password/');
    });

    it('stays on the address people came to: its own, with or without www', function (string $host) {
        expect(resetLinkAsked("http://{$host}", ['X-Forwarded-Proto' => 'https']))->toStartWith("https://{$host}/reset-password/");
    })->with(['www.mtav.uy', 'mtav.uy']);

    it('is not fooled by a host that only ends like its own', function () {
        expect(resetLinkAsked('http://evilmtav.uy', ['X-Forwarded-Proto' => 'https']))->toStartWith('https://www.mtav.uy/');
    });

    it('still answers the health check, which asks its own 127.0.0.1', function () {
        $this->get('http://127.0.0.1:8080/up')->assertOk();
    });

    it('still takes a signed link when the proxy in front names an inner host', function () {
        $person = aUser(['email_verified_at' => null]);
        URL::forceRootUrl('https://www.mtav.uy');
        URL::forceScheme('https');
        $link = URL::signedRoute('verification.verify', ['id' => $person->id, 'hash' => sha1($person->email)]);
        URL::forceRootUrl(null);
        URL::forceScheme(null);

        $this->as($person)->withHeader('X-Forwarded-Proto', 'https')
            ->get(str_replace('https://www.mtav.uy', 'http://127.0.0.1:8002', $link))
            ->assertRedirect();

        expect($person->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('keeps a port its own address has', function () {
        config(['app.url' => 'http://localhost:8002']);

        expect(resetLinkAsked('http://evil.example'))->toStartWith('http://localhost:8002/reset-password/');
    });
});

it('answers on any address where no deployment is set', function () {
    expect(resetLinkAsked('http://mtav.test:8000'))->toStartWith('http://mtav.test:8000/reset-password/');
});
