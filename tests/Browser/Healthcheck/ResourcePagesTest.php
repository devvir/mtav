<?php

use App\Models\User;

uses()->group('Browser.Healthcheck');

/**
 * Define resource pages to be tested, and exceptions for members/admins.
 *
 *  - id         id of sample resource to use for show/edit
 *  - no-member  routes not accessible to members for the given resource
 *  - no-admin   routes not accessible to admins for the given resource
 */
$routes = collect([
    'projects'   => ['id' => 1,   'no-member' => ['index', 'show', 'create', 'edit'], 'no-admin' => ['index', 'create']],
    'admins'     => ['id' => 11,  'no-member' => ['create', 'edit']],
    'members'    => ['id' => 105, 'no-member' => ['edit']],
    'families'   => ['id' => 4,   'no-member' => ['create']],
    'units'      => ['id' => 1,   'no-member' => ['create', 'edit']],
    'unit_types' => ['id' => 2,   'no-member' => ['create', 'edit']],
    'events'     => ['id' => 2,   'no-member' => ['create', 'edit']],
    // 'Media'    => ['id' => 1], // TODO : add media to universe.sql
])->map(function (array $config, string $namespace) {
    $routes = collect([
        'index'  => [$namespace . '.index'],
        'show'   => [$namespace . '.show', $config['id']],
        'create' => [$namespace . '.create'],
        'edit'   => [$namespace . '.edit', $config['id']],
    ]);

    return [ /** Apply 'except' filters, to get the allowed routes for each role */
        1    /** Superadmin */ => $routes,
        11   /** Admin */      => $routes->except($config['no-admin'] ?? []),
        102  /** Member */     => $routes->except($config['no-member'] ?? []),
    ];
});

/**
 * 0:   Superadmin  (#1)    in multi-project context,
 * 1:   Superadmin  (#1)    in Project #1
 * 11:  Admin       (#11)   in Project #1
 * 102: Member      (#102)  in Project #1
 */
(collect([0, 1, 11, 102]))->each(function (int $userId) use ($routes) {
    test("User {$userId}", function (string $variant, string $route, ?int $id = null) use ($userId) {
        dump("User {$userId} ({$variant}), visits route {$route}" . ($id ? " (resourceId: {$id})" : ''));

        /** Hide @diffForHumans labels' discrepancies (e.g. "2 hours ago", and not 1-5 seconds) */
        $this->travel(48)->hours();

        /** Set Project #1 as current (except for Superadmin in multi-project context, userId "0") */
        $userId && setFirstProjectAsCurrent();

        /** Authenticate as the given user */
        $this->actingAs(once(fn () => User::find($userId ?: 1)));

        /** Set current variant's locale */
        config()->set('app.locale', $variant === 'dark-en-desktop' ? 'en' : 'es_UY');

        /** Set current variant's theme/device */
        $page = $variant === 'dark-en-desktop'
            ? visit(route($route, $id))->inDarkMode()
            : visit(route($route, $id))->on()->mobile();

        /** Take screenshot for manual review, plus automatic visual regression assertions */
        $page
            ->screenshot(filename: "healthcheck-user-{$userId}-{$variant}-{$route}.png")
            ->assertNoSmoke() /** No console logs or javascript errors */
            ->assertScreenshotMatches();
    })->with([
        'dark-en-desktop',
        'light-es-mobile',
    ])->with(   /** Map all-roles routes to the list of role-specific routes, flattened */
        $routes /** For multi-project context (userId = 0), avoid project-only resources */
            ->unless($userId, fn ($r) => $r->except('units', 'unit_types', 'events'))
            ->map(fn ($route) => $route[$userId ?: 1]->toArray())->flatten(1)
    );
});
