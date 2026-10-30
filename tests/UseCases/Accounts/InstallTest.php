<?php

/**
 * The app installs on a device like any other: under the deployment's own name, with its
 * icons, opening in its own window.
 */

it('describes the app for installing it, under the deployment\'s name', function () {
    config(['app.name' => 'Cooperativas del Sur']);

    $manifest = $this->get(route('manifest'))->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json');

    expect($manifest->json())
        ->name->toBe('Cooperativas del Sur')
        ->display->toBe('standalone')
        ->start_url->toBe('/');

    foreach ($manifest->json('icons') as $icon) {
        expect(public_path($icon['src']))->toBeFile();
    }
    expect(collect($manifest->json('icons'))->pluck('purpose'))->toContain('maskable');
});

it('points every page to it', function () {
    $this->get(route('manual'))->assertSee('rel="manifest" href="/manifest.webmanifest"', escape: false);
});
