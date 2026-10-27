<?php

use Tests\TestCase;

/**
 * Helpers and expectations: tests/Helpers/*.php (auto-loaded by Pest).
 */
pest()
    ->extend(TestCase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Arch', 'Unit', 'UseCases', 'Stress');
