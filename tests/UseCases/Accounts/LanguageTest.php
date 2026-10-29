<?php

/**
 * The language people read: the one they picked; else their browser's, when the platform
 * has it; else English. Signed in without a choice, they keep the one found.
 */

function localeOf($test, string $browser = 'en-US'): string
{
    return $test->get(route('login'), ['Accept-Language' => $browser])->inertiaProps('locale');
}

it("speaks the browser's language when it has it", function () {
    expect(localeOf($this, 'fr-FR,fr;q=0.9'))->toBe('fr');
    expect(localeOf($this, 'es-UY'))->toBe('es_UY');
});

it("finds the browser's language by its family", function () {
    expect(localeOf($this, 'es-AR,es;q=0.9'))->toBe('es_UY');
    expect(localeOf($this, 'pt-PT'))->toBe('pt_BR');
});

it('speaks English when it has none of the browser\'s languages', function () {
    expect(localeOf($this, 'de-DE,de;q=0.9,ja;q=0.5'))->toBe('en');
});

it('speaks what the person picked, whatever their browser', function () {
    $this->as(aUser(['locale' => 'it']));

    expect($this->get(route('profile.edit'), ['Accept-Language' => 'fr-FR'])->inertiaProps('locale'))->toBe('it');
});

it('keeps the language found for someone who never picked one, for their mail too', function () {
    $user = aUser(['locale' => null]);
    $this->as($user)->get(route('profile.edit'), ['Accept-Language' => 'pt-BR']);

    expect($user->fresh()->locale)->toBe('pt_BR');
});

it('speaks what a guest picked for the rest of the visit', function () {
    $this->put(route('language'), ['locale' => 'it'])->assertSessionHasNoErrors();

    expect($this->get(route('home'), ['Accept-Language' => 'fr-FR'])->inertiaProps('locale'))->toBe('it');
});

it('gives the account a guest signs in to the language they picked', function () {
    $user = aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1', 'locale' => 'en']);

    $this->put(route('language'), ['locale' => 'pt_BR']);
    $this->post(route('login'), ['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);
    $this->get(route('profile.edit'));

    expect($user->fresh()->locale)->toBe('pt_BR');
});

it('changes the language of someone signed in, and refuses one it does not have', function () {
    $user = aUser(['locale' => 'en']);

    $this->as($user)->put(route('language'), ['locale' => 'fr'])->assertSessionHasNoErrors();
    $this->as($user)->put(route('language'), ['locale' => 'de'])->assertSessionHasErrors('locale');

    expect($user->fresh()->locale)->toBe('fr');
});
