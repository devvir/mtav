<?php

use App\Models\Family;
use App\Models\Unit;
use App\Services\LotteryService;

uses()->group('Feature.Lottery');

test('Lottery service removes invalid preferences and fills with remaining candidates', function () {
    // Family #4: unit_type_id = 1 (Project #1)
    // Unit #1: unit_type_id = 2 (invalid for Family #4)
    // No units exist with unit_type_id = 1 in universe (Type #1: NO units)
    $family = Family::find(4); // unit_type_id = 1
    $invalidUnit = Unit::find(1); // unit_type_id = 2 (different from family's type)

    $family->preferences()->attach($invalidUnit->id, [
        'order' => 1,
    ]);

    $lotteryService = app(LotteryService::class);
    $preferences = $lotteryService->preferences($family);

    // Should have cleaned invalid preferences
    // Since Family #4 has unit_type_id = 1 and there are no units with type 1,
    // the result should be empty after sanitization
    $preferenceIds = $preferences->pluck('id')->toArray();

    expect($preferenceIds)->not->toContain($invalidUnit->id) // Invalid unit should be removed
        ->toBeEmpty(); // No valid units exist for this unit type
});

test('Lottery service returns all unit type units as candidates when family has no preferences', function () {
    // Family #9: unit_type_id = 3
    // Units #2, #3: unit_type_id = 3 (matching family's type)

    $family = Family::find(9); // unit_type_id = 3
    $lotteryService = app(LotteryService::class);
    $preferences = $lotteryService->preferences($family);

    // Should return all units of the family's type as candidates
    // Type #3 has 2 units (#2, #3) but #3 is soft-deleted, so expect 1 unit
    expect($preferences->count())->toBe(1)
        ->and($preferences->pluck('id')->toArray())->toContain(2); // Unit #2
});

test('Lottery service preserves user-defined preference order and appends remaining units', function () {
    // Family #9: unit_type_id = 3
    // Units #2, #3: unit_type_id = 3 (but #3 is deleted, so only #2 available)
    // We'll use Family #13 instead: unit_type_id = 4
    // Units #4, #5: unit_type_id = 4

    $family = Family::find(13); // unit_type_id = 4
    $unit4 = Unit::find(4); // unit_type_id = 4
    $unit5 = Unit::find(5); // unit_type_id = 4

    // Set preferences in specific order (unit5 first, unit4 second)
    $family->preferences()->attach($unit5->id, [
        'order' => 1,
    ]);
    $family->preferences()->attach($unit4->id, [
        'order' => 2,
    ]);

    $lotteryService = app(LotteryService::class);
    $preferences = $lotteryService->preferences($family);

    $preferenceIds = $preferences->pluck('id')->toArray();

    // Should maintain preference order: unit5 (order 1) first, then unit4 (order 2)
    expect($preferenceIds[0])->toBe($unit5->id)
        ->and($preferenceIds[1])->toBe($unit4->id)
        ->and($preferences->count())->toBe(2); // Should have both units for this type
});
