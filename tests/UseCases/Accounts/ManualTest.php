<?php

/**
 * Every guide and every list of questions is for anyone, as a document of its own at an
 * address to share, in the browser's language; the member's, to read before joining. Inside
 * the app, the pages that show them need a sign-in.
 */

it('shows anyone the member manual, in their browser\'s language', function () {
    $english = $this->get(route('manual'), ['Accept-Language' => 'en-US']);
    $english->assertOk()->assertInertia(fn ($page) => $page->component('PublicDocument'));
    expect($english->inertiaProps('content'))->toContain('Member guide');

    expect($this->get(route('manual'), ['Accept-Language' => 'es-UY'])->inertiaProps('content'))
        ->toContain('Guía para cooperativistas');
});

it('shows anyone the admins\' and the superadmins\' guides too, each titled as it names itself', function () {
    $admins = $this->get(route('manual', 'admins'), ['Accept-Language' => 'es-UY'])->assertOk();
    $admins->assertInertia(fn ($page) => $page->component('PublicDocument')->where('title', 'Guía para administradores'));
    expect($admins->inertiaProps('content'))->toContain('Crear un proyecto');

    $superadmins = $this->get(route('manual', 'superadmins'), ['Accept-Language' => 'es-UY'])->assertOk();
    expect($superadmins->inertiaProps('content'))->toContain('Guía para superadministradores');

    $this->get('/manual/members')->assertNotFound();
});

it('shows anyone the questions, each list ending where its guide is', function () {
    $members = $this->get(route('manual.faq'), ['Accept-Language' => 'es-UY'])->assertOk();
    $members->assertInertia(fn ($page) => $page->component('PublicDocument')->where('title', 'Preguntas frecuentes'));
    expect($members->inertiaProps('content'))
        ->toContain('<h2>')
        ->toContain('href="' . route('manual') . '"');

    $admins = $this->get(route('manual.admins.faq'), ['Accept-Language' => 'es-UY'])->assertOk();
    expect($admins->inertiaProps('content'))
        ->toContain('¿Por qué mi proyecto es limitado?')
        ->toContain('href="' . route('manual', 'admins') . '"');
});

it('keeps the pages inside the app for those signed in', function () {
    $this->get(route('documentation.guide'))->assertRedirect(route('login'));
    $this->get(route('documentation.faq'))->assertRedirect(route('login'));
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

it('has every variant\'s privacy page in every language it speaks', function () {
    foreach (shippedVariants() as $file) {
        $variant = App\Support\VariantFile::load($file);
        foreach (glob(lang_path('*.json')) as $language) {
            expect(resource_path('views/legal/' . basename($language, '.json') . "/{$variant['privacy']}"))->toBeFile();
        }
    }
});

it('names the deployment wherever a guide speaks of the app, in every language', function () {
    config(['app.name' => 'Cooperativas del Sur']);

    foreach (['en-US', 'es-UY', 'fr', 'it', 'pt-BR'] as $language) {
        expect($this->get(route('manual'), ['Accept-Language' => $language])->inertiaProps('content'))
            ->toContain('Cooperativas del Sur')
            ->not->toContain('MTAV')
            ->not->toContain('{app}');
    }

    $project = aProject();
    $this->as(anAdminOf($project), $project);
    expect($this->get(route('documentation.guide'))->inertiaProps('content'))
        ->toContain('Cooperativas del Sur')
        ->not->toContain('MTAV')
        ->not->toContain('{app}');
});

it('keeps a name with odd characters from becoming part of a guide\'s page', function () {
    config(['app.name' => 'Sur <b>& co</b>']);

    expect($this->get(route('manual'))->inertiaProps('content'))
        ->toContain('Sur &lt;b&gt;&amp; co&lt;/b&gt;')
        ->not->toContain('<b>&');
});

it('sends the guide and the questions only what they show', function () {
    $project = aProject();
    $this->as(anAdminOf($project), $project);

    $this->get(route('documentation.guide'))->assertInertia(fn ($page) => $page->missing('userRole'));
    $this->get(route('documentation.faq'))->assertInertia(fn ($page) => $page->missing('userRole'));
});
