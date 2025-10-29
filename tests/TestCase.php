<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\Utilities;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;
    use Utilities;

    static $bootstrapped = false;

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Disable Vite in tests to avoid manifest.json dependency
         * @see [Laracasts] Learn Laravel and Vite @ Lesson 7
         */
        $this->withoutVite();

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
