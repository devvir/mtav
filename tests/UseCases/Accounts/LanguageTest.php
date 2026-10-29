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
