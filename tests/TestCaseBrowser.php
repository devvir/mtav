<?php

namespace Tests;

abstract class TestCaseBrowser extends TestCase
{
    protected function setUp(): void
    {
        static::$bootstrapped = false;

        parent::setUp();
    }
}
