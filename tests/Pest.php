<?php

use Illuminate\Contracts\Http\Kernel;
use Tests\Browser\Support\ReadUploadsLikePhp;
use Tests\TestCase;

/**
 * Tests empty the database they run on: only ever a test environment's own (./mtav pest,
 * ./mtav e2e, CI), never the one a developer works in, whose container says it is "local"
 */
if (getenv('APP_ENV') !== 'testing') {
    fwrite(STDERR, "Refusing to run: APP_ENV is '" . getenv('APP_ENV') . "', not 'testing'. Use ./mtav pest or ./mtav e2e.\n");

    exit(1);
}

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
    ->beforeEach(fn () => app(Kernel::class)->prependMiddleware(ReadUploadsLikePhp::class))
    ->in('Browser');

pest()->browser()->timeout(20_000);
