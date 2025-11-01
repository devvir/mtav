<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Load the universe fixture into the test database.
 *
 * Establishes a known, consistent state with predictable test data.
 */
function loadUniverse(): void
{
    Artisan::call('migrate:fresh');

    $sql = file_get_contents(__DIR__.'/../_fixtures/universe.sql');

    DB::unprepared($sql);
}
