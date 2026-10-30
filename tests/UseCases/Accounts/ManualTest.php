<?php

/**
 * The member manual is for anyone, to share or read before joining: in the browser's
 * language. The admins' and superadmins' stay inside the app.
 */

it('shows anyone the member manual, in their browser\'s language', function () {
    $english = $this->get(route('manual'), ['Accept-Language' => 'en-US']);
    $english->assertOk()->assertInertia(fn ($page) => $page->component('PublicDocument'));
    expect($english->inertiaProps('content'))->toContain('Member guide');

    expect($this->get(route('manual'), ['Accept-Language' => 'es-UY'])->inertiaProps('content'))
        ->toContain('Guía para cooperativistas');
});

it('keeps the admin guide for those signed in', function () {
    $this->get(route('documentation.guide'))->assertRedirect(route('login'));
});

it('says anyone what is kept about them and whom to write, in their language', function () {
    config(['app.name' => 'Cooperativas del Sur', 'app.contact' => 'datos@example.org']);

    $english = $this->get(route('privacy'), ['Accept-Language' => 'en-US'])->assertOk();
    $english->assertInertia(fn ($page) => $page->component('PublicDocument')->where('title', 'Privacy'));
    expect($english->inertiaProps('content'))
        ->toContain('Cooperativas del Sur keeps about you')
        ->toContain('mailto:datos@example.org')
        ->toContain('Ley N.º 18.331')
        ->not->toContain('{app}')
        ->not->toContain('{contact}');

    expect($this->get(route('privacy'), ['Accept-Language' => 'es-UY'])->inertiaProps('content'))
        ->toContain('Tu cédula, si la agregas, la ven solo tú y los administradores');
});

it('has whom to write about data without a contact set: the owner', function () {
    config(['app.contact' => null, 'app.owner.email' => 'owner@example.org']);

    expect($this->get(route('privacy'))->inertiaProps('content'))->toContain('mailto:owner@example.org');
});

it('has the privacy page in every language it speaks', function () {
    foreach (glob(lang_path('*.json')) as $file) {
        $locale = basename($file, '.json');
        expect(resource_path("views/legal/{$locale}/privacy.html"))->toBeFile();
    }
});
