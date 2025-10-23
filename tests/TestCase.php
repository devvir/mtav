<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // @see [Laracasts] Learn Laravel and Vite @ Lesson 7
        // Disable Vite in tests to avoid manifest.json dependency
        $this->withoutVite();
    }
}
