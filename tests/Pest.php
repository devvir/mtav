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

/**
 * Archived suite, phased out but still in use
 */
require_once __DIR__.'/__Archive/Helpers/UserHelpers.php';
require_once __DIR__.'/__Archive/Helpers/ProjectHelpers.php';
require_once __DIR__.'/__Archive/Helpers/FamilyHelpers.php';
require_once __DIR__.'/__Archive/Helpers/InertiaHelpers.php';