<?php

/**
 * The member manual is for anyone, to share or read before joining: in the browser's
 * language. The admins' and superadmins' stay inside the app.
 */

it('shows anyone the member manual, in their browser\'s language', function () {
    $english = $this->get(route('manual'), ['Accept-Language' => 'en-US']);
    $english->assertOk()->assertInertia(fn ($page) => $page->component('Manual'));
    expect($english->inertiaProps('content'))->toContain('Member guide');

    expect($this->get(route('manual'), ['Accept-Language' => 'es-UY'])->inertiaProps('content'))
        ->toContain('Guía para cooperativistas');
});

it('keeps the admin guide for those signed in', function () {
    $this->get(route('documentation.guide'))->assertRedirect(route('login'));
});
