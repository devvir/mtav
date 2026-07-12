<?php

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\TestCaseBrowser;

/**
 * Unit/Feature testing
 */
pest()
    ->extend(TestCase::class)
    // NOTE: Pest keeps only ONE beforeEach hook per uses() chain (a second
    // ->beforeEach() overwrites the first), so both steps share one closure.
    ->beforeEach(function () {
        $this->withoutVite();
        DB::beginTransaction();
    })
    ->afterEach(fn () => DB::rollback())
    ->in('Unit', 'Feature', 'Stress');

/**
 * Browser testing
 */
pest()
    ->extend(TestCaseBrowser::class)
    ->in('Browser');

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
