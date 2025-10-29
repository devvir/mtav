<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Custom Expectations
 */
expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * Global Concerns
 */
uses(TestCase::class)
    ->use(DatabaseTransactions::class)
    ->afterEach(fn () => resetCurrentProject())
    ->in('Authentication');


/**
 * Archived suite, phased out but still in use
 */
require_once __DIR__.'/__Archive/Helpers/UserHelpers.php';
require_once __DIR__.'/__Archive/Helpers/ProjectHelpers.php';
require_once __DIR__.'/__Archive/Helpers/FamilyHelpers.php';
require_once __DIR__.'/__Archive/Helpers/InertiaHelpers.php';

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('__Archive');