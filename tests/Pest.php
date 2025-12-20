<?php

use Illuminate\Support\Facades\DB;
use Pest\Browser\Playwright\Servers\ExternalPlaywrightServer;
use Tests\TestCase;
use Tests\TestCaseBrowser;

/**
 * Unit/Feature testing
 */
pest()
    ->extend(TestCase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->beforeEach(fn () => DB::beginTransaction())
    ->afterEach(fn () => DB::rollback())
    ->in('Unit', 'Feature', 'Stress');

/**
 * Browser testing
 */
pest()
    ->extend(TestCaseBrowser::class)
    ->in('Browser');

ExternalPlaywrightServer::use('playwright', 5000);

/**
 * Custom Expectations
 *
 * @see ./Helpers/expectations.php
 */

/**
 * Helper Functions
 *
 * @see ./Helpers/*.php (auto-loaded by Pest)
 */
