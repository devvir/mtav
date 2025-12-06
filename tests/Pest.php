<?php

use Illuminate\Support\Facades\DB;
use Pest\Browser\Playwright\Servers\ExternalPlaywrightServer;
use Tests\TestCase;

/**
 * Set global TestCase class and hooks.
 */
pest()
    ->extend(TestCase::class)
    ->beforeEach(fn () => DB::beginTransaction())
    ->afterEach(fn () => DB::rollback());

/**
 * Disable Vite in backend tests to avoid asset dependency
 * @see [Laracasts] Learn Laravel and Vite @ Lesson 7
 */
pest()
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Unit', 'Feature');

/**
 * Browser testing
 */
ExternalPlaywrightServer::use('playwright', 5000);

/**
 * Custom Expectations
 *
 * @see ./Helpers/expectations.php
 */

/**
 * Helper Functions
 */
require_once __DIR__ . '/Helpers/formService.php';

/**
 * Archived suite, phased out but still in use
 */
require_once __DIR__ . '/__Archive/Helpers/UserHelpers.php';
require_once __DIR__ . '/__Archive/Helpers/ProjectHelpers.php';
require_once __DIR__ . '/__Archive/Helpers/FamilyHelpers.php';
require_once __DIR__ . '/__Archive/Helpers/InertiaHelpers.php';
