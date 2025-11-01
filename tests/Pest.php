<?php

use Tests\TestCase;

/**
 * Set global TestCase class and hooks.
 */
pest()
    ->extend(TestCase::class)
    ->afterEach(fn () => resetCurrentProject());

/**
 * Custom Expectations
 */
expect()->extend('toBeOne', fn () => $this->toBe(1));

expect()->extend('toBeOk', function () {
    $this->toBeInstanceOf(\Illuminate\Testing\TestResponse::class);
    $this->status()->toBe(200);
});

expect()->extend('toRedirectTo', function (string $route, array $params = []) {
    $this->toBeInstanceOf(\Illuminate\Testing\TestResponse::class);
    $this->status()->toBe(302);
    $this->getTargetUrl()->toBe(route($route, $params));

    return $this;
});

/**
 * Archived suite, phased out but still in use
 */
require_once __DIR__.'/__Archive/Helpers/UserHelpers.php';
require_once __DIR__.'/__Archive/Helpers/ProjectHelpers.php';
require_once __DIR__.'/__Archive/Helpers/FamilyHelpers.php';
require_once __DIR__.'/__Archive/Helpers/InertiaHelpers.php';