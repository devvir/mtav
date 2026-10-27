<?php

use Tests\TestCase;

/**
 * Helpers and expectations: tests/Helpers/*.php (auto-loaded by Pest).
 */
pest()
    ->extend(TestCase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Arch', 'Unit', 'UseCases', 'Stress');

/**
 * Browser tests load the real, built frontend. The app runs in-process: its first
 * requests (cold caches, compiled views) need more than Playwright's default 5s.
 */
pest()
    ->extend(TestCase::class)
    ->in('Browser');

pest()->browser()->timeout(20_000);
