<?php

use App\Models\Plan;
use App\Services\Plan\Defaults;

/**
 * Where a unit added without a place lands on the plan: in rows, inside the boundary's
 * width, each a square of some size, whatever the boundary
 */

function boundaryOf(float $width, float $height = 600): Plan
{
    return new Plan(['polygon' => [[0, 0], [$width, 0], [$width, $height], [0, $height]]]);
}

/** @return array{0: float, 1: float}  width, height */
function sideOf(array $corners): array
{
    $xs = array_column($corners, 0);
    $ys = array_column($corners, 1);

    return [max($xs) - min($xs), max($ys) - min($ys)];
}

it('gives a unit a square of some size, with its corners in order, however narrow the boundary', function (float $width) {
    $corners = app(Defaults::class)->getNextAvailablePosition(boundaryOf($width));
    [$topLeft, $topRight, $bottomRight] = $corners;

    expect(sideOf($corners)[0])->toBeGreaterThan(0)
        ->and(sideOf($corners)[0])->toBe(sideOf($corners)[1])
        ->and($topRight[0])->toBeGreaterThan($topLeft[0])
        ->and($bottomRight[1])->toBeGreaterThan($topRight[1]);
})->with([10, 60, 100, 119, 120, 175, 229]);

it('keeps the size it always gave where a row of units fits', function () {
    // floor((800 - 10) / 5 / 11) * 2.5 = 35: a side of 2 * (35 - 5)
    expect(sideOf(app(Defaults::class)->getNextAvailablePosition(boundaryOf(800))))->toEqual([60, 60]);
});

it('takes a size given, centred on the same spot', function () {
    expect(sideOf(app(Defaults::class)->getNextAvailablePosition(boundaryOf(50), [30, 20])))->toEqual([30, 20]);
});
