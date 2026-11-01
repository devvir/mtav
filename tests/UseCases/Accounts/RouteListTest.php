<?php

/**
 * The routes a browser is told of: the platform console's, only to those who enter it.
 */

function routesShownOn(string $page, $test): array
{
    return array_keys($test->get($page)->inertiaProps('routes')['routes']);
}

it('tells a visitor none of the console\'s routes, and the ones a visitor needs', function () {
    $routes = routesShownOn('/', $this);

    expect(array_filter($routes, fn ($name) => str_starts_with($name, 'admin.')))->toBe([])
        ->and($routes)->toContain('login', 'home');
});

it('tells someone in a project none of them either, admin or not', function () {
    $project = aProject();

    foreach ([aMemberOf(aFamily($project)), anAdminOf($project)] as $user) {
        $routes = routesShownOn(route('projects.index', absolute: false), $this->as($user));

        expect(array_filter($routes, fn ($name) => str_starts_with($name, 'admin.')))->toBe([])
            ->and($routes)->toContain('projects.show', 'manage.overview');
    }
});

it('tells a superadmin all of them', function () {
    $routes = routesShownOn(route('projects.index', absolute: false), $this->as(aSuperadmin()));

    expect($routes)->toContain('admin.projects.index', 'admin.deployments.index', 'projects.show');
});

it('remembers each list under its own name, so signing in brings the longer one', function () {
    $once = fn ($response) => array_keys($response->viewData('page')['onceProps'] ?? []);

    expect($once($this->get('/')))->toContain('routes')->not->toContain('routes.console');
    expect($once($this->as(aSuperadmin())->get(route('projects.index'))))->toContain('routes.console')->not->toContain('routes');
});
