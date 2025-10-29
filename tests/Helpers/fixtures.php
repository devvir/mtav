<?php

use Illuminate\Support\Facades\DB;

/**
 * Load the universe fixture into the test database.
 *
 * Establishes a known, consistent state with predictable test data.
 */
function loadUniverse(): void
{
    $sql = file_get_contents(__DIR__.'/../_fixtures/universe.sql');

    DB::unprepared($sql);
}
