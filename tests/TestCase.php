<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\Utilities;

abstract class TestCase extends BaseTestCase
{
    use Utilities;

    public static $bootstrapped = false;

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Seed the test database (once for the whole suite)
         */
        static::$bootstrapped || loadUniverse();

        /**
         * Flag suite-wide bootstrap as completed.
         */
        static::$bootstrapped = true;
    }
}
