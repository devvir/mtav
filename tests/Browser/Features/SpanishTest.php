<?php

/**
 * The whole UI speaks Spanish when the browser does.
 */

it('speaks Spanish in the member app', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type);
    $this->as(aMemberOf(aFamily($project, $type)));

    visit(route('lottery.show', $project))->withLocale('es-UY')
        ->assertSee('Sorteo')
        ->assertSee('Tus preferencias')
        ->assertSee('Viviendas')
        ->assertDontSee('Your preferences')
        ->assertNoSmoke();
});

it('speaks Spanish in the admin area and in confirmations', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    visit(route('manage.settings.edit', $project))->withLocale('es-UY')
        ->assertSee('Configuración')
        ->click(button('Eliminar el proyecto'))
        ->assertSee('Escribe')
        ->assertDontSee('Delete the project')
        ->assertNoSmoke();
});

it('speaks Spanish when signing in', function () {
    visit(route('login'))->withLocale('es-UY')->assertSee('Entrar')->assertSee('¿Olvidaste tu contraseña?');
});
