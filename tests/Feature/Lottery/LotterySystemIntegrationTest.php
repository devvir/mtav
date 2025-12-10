<?php

// Copilot - Pending review

use App\Models\Event;
use App\Models\Family;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Config;

uses()->group('Stress.Lottery.System');

beforeEach(function () {
    // Use TestSolver for system integration tests - no need for expensive GLPK binary
    config()->set('lottery.default', 'test');
});

describe('Lottery System End-to-End Integration Tests', function () {
    test('complete lottery execution with balanced families and units', function () {
        // Use Project #4 from universe.sql - perfectly balanced
        // Type #7: 2 families (#16, #22), 2 units (#13, #14)
        // Type #8: 2 families (#17, #27), 2 units (#15, #16)
        // Type #9: 2 families (#18, #28), 2 units (#17, #18)
        setCurrentProject(4);
        $admin = User::find(13); // Admin #13 manages Project #4
        $lottery = Event::find(13); // Past lottery for Project #4
        $lottery->update(['is_published' => true]); // Ensure published

        // Get all families and units for Project #4
        $families = Family::whereIn('id', [16, 17, 18, 22, 27, 28])->get();
        $units = Unit::whereIn('id', [13, 14, 15, 16, 17, 18])->get();

        // Set diverse preferences for each family (each wants different top choice)
        $preferences = [
            16 => [13, 14], // Type #7 family prefers 13, 14
            22 => [14, 13], // Type #7 family prefers 14, 13
            17 => [15, 16], // Type #8 family prefers 15, 16
            27 => [16, 15], // Type #8 family prefers 16, 15
            18 => [17, 18], // Type #9 family prefers 17, 18
            28 => [18, 17], // Type #9 family prefers 18, 17
        ];

        foreach ($preferences as $familyId => $unitIds) {
            $family = Family::find($familyId);
            $syncData = [];
            foreach ($unitIds as $order => $unitId) {
                $syncData[$unitId] = ['order' => $order + 1];
            }
            $family->preferences()->sync($syncData);
        }

        // Execute lottery
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13, redirects: false);

        // Verify all units are assigned
        $units->fresh()->each(function ($unit) {
            expect($unit->family_id)->not->toBeNull('All units should be assigned');
        });

        // Verify all families have a unit
        $families->fresh()->each(function ($family) {
            $assignedUnit = Unit::where('family_id', $family->id)->first();
            expect($assignedUnit)->not->toBeNull('All families should have a unit');
        });

        // Verify no duplicate assignments
        $assignedFamilyIds = Unit::whereIn('id', [13, 14, 15, 16, 17, 18])
            ->pluck('family_id')
            ->toArray();
        expect(count($assignedFamilyIds))->toBe(count(array_unique($assignedFamilyIds)));

        // Verify lottery is marked as complete (soft-deleted)
        expect($lottery->fresh()->trashed())->toBeTrue();
    });

    test('lottery with unbalanced units (more units than families)', function () {
        // Use Project #2 from universe.sql
        // Type #4: 2 families (#13, #14), 2 units (#4, #5) - BALANCED
        // Type #5: 2 families (#15, #26), 2 units (#6, #7) - BALANCED
        // Type #6: 0 families, 2 units (#8, #9) - UNBALANCED (2 orphan units)
        // Total: 4 families, 6 units = 2 orphan units expected
        setCurrentProject(2);
        $lottery = Event::find(6); // Future lottery for Project #2
        $lottery->update(['start_date' => now()->subDay()]);

        $families = Family::whereIn('id', [13, 14, 15, 26])->get();
        $units = Unit::whereIn('id', [4, 5, 6, 7, 8, 9])->get();

        // Set preferences
        $preferences = [
            13 => [4, 5], // Type #4 family
            14 => [5, 4], // Type #4 family
            15 => [6, 7], // Type #5 family
            26 => [7, 6], // Type #5 family
        ];

        foreach ($preferences as $familyId => $unitIds) {
            $family = Family::find($familyId);
            $syncData = [];
            foreach ($unitIds as $order => $unitId) {
                $syncData[$unitId] = ['order' => $order + 1];
            }
            $family->preferences()->sync($syncData);
        }

        // Execute with mismatch-allowed option (4 families, 6 units = mismatch)
        $this->submitFormToRoute(['lottery.execute', $lottery->id], data: ['options' => ['mismatch-allowed']], asAdmin: 12, redirects: false);

        // All families should be assigned
        $families->fresh()->each(function ($family) {
            $assignedUnit = Unit::where('family_id', $family->id)->first();
            expect($assignedUnit)->not->toBeNull();
        });

        // 2 units should remain unassigned (6 units - 4 families = 2 orphans: units #8, #9)
        $unassignedCount = Unit::whereIn('id', [4, 5, 6, 7, 8, 9])
            ->whereNull('family_id')
            ->count();
        expect($unassignedCount)->toBe(2);
    });

    test('lottery optimization respects preferences', function () {
        // Use Type #7 from Project #4 (2 families, 2 units)
        // Family #16 wants Unit #13 first
        // Family #22 wants Unit #14 first
        // Optimal: both get their first choice
        setCurrentProject(4);
        $admin = User::find(13);
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true]);

        $family16 = Family::find(16);
        $family22 = Family::find(22);

        // Set preferences - each family wants different unit first
        $family16->preferences()->sync([
            13 => ['order' => 1], // Unit #13 first choice
            14 => ['order' => 2], // Unit #14 second choice
        ]);

        $family22->preferences()->sync([
            14 => ['order' => 1], // Unit #14 first choice
            13 => ['order' => 2], // Unit #13 second choice
        ]);

        // Execute
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // With optimal assignment, each family should get their first choice
        $family16Unit = Unit::where('family_id', 16)->first();
        $family22Unit = Unit::where('family_id', 22)->first();

        expect($family16Unit->id)->toBe(13);
        expect($family22Unit->id)->toBe(14);
    });

    test('max-min fairness is achieved', function () {
        // Use Type #8 from Project #4 (2 families, 2 units)
        // Both families want Unit #15 first (conflict scenario)
        setCurrentProject(4);
        $admin = User::find(13);
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true]);

        $family17 = Family::find(17);
        $family27 = Family::find(27);

        // Both families prefer unit15 first (creates conflict)
        $family17->preferences()->sync([
            15 => ['order' => 1], // Unit #15 first choice
            16 => ['order' => 2], // Unit #16 second choice
        ]);

        $family27->preferences()->sync([
            15 => ['order' => 1], // Unit #15 first choice (same!)
            16 => ['order' => 2], // Unit #16 second choice
        ]);

        // Execute
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // One family gets unit15 (rank 1), other gets unit16 (rank 2)
        // Max-min ensures worst-case is rank 2 (can't do better when both want same unit)
        $family17Unit = Unit::where('family_id', 17)->first();
        $family27Unit = Unit::where('family_id', 27)->first();

        expect($family17Unit->id)->not->toBe($family27Unit->id);
        expect([$family17Unit->id, $family27Unit->id])->toContain(15);
        expect([$family17Unit->id, $family27Unit->id])->toContain(16);
    });

    test('multi-unit-type lottery execution', function () {
        // Use Project #4 from universe.sql - has 3 unit types
        // Type #7: 2 families (#16, #22), 2 units (#13, #14)
        // Type #8: 2 families (#17, #27), 2 units (#15, #16)
        // Type #9: 2 families (#18, #28), 2 units (#17, #18)
        setCurrentProject(4);
        $admin = User::find(13);
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true]);

        // Get families by type
        $type7Families = Family::whereIn('id', [16, 22])->get();
        $type8Families = Family::whereIn('id', [17, 27])->get();
        $type9Families = Family::whereIn('id', [18, 28])->get();

        // Get units by type
        $type7Units = Unit::whereIn('id', [13, 14])->get();
        $type8Units = Unit::whereIn('id', [15, 16])->get();
        $type9Units = Unit::whereIn('id', [17, 18])->get();

        // Set preferences for Type #7 families
        $type7Families->each(function ($family) use ($type7Units) {
            $preferences = $type7Units->mapWithKeys(function ($unit, $idx) {
                return [$unit->id => ['order' => $idx + 1]];
            })->toArray();
            $family->preferences()->sync($preferences);
        });

        // Set preferences for Type #8 families
        $type8Families->each(function ($family) use ($type8Units) {
            $preferences = $type8Units->mapWithKeys(function ($unit, $idx) {
                return [$unit->id => ['order' => $idx + 1]];
            })->toArray();
            $family->preferences()->sync($preferences);
        });

        // Set preferences for Type #9 families
        $type9Families->each(function ($family) use ($type9Units) {
            $preferences = $type9Units->mapWithKeys(function ($unit, $idx) {
                return [$unit->id => ['order' => $idx + 1]];
            })->toArray();
            $family->preferences()->sync($preferences);
        });

        // Execute
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // Verify all Type #7 units assigned to Type #7 families
        $type7Units->fresh()->each(function ($unit) use ($type7Families) {
            expect($unit->family_id)->not->toBeNull();
            expect($type7Families->pluck('id'))->toContain($unit->family_id);
        });

        // Verify all Type #8 units assigned to Type #8 families
        $type8Units->fresh()->each(function ($unit) use ($type8Families) {
            expect($unit->family_id)->not->toBeNull();
            expect($type8Families->pluck('id'))->toContain($unit->family_id);
        });

        // Verify all Type #9 units assigned to Type #9 families
        $type9Units->fresh()->each(function ($unit) use ($type9Families) {
            expect($unit->family_id)->not->toBeNull();
            expect($type9Families->pluck('id'))->toContain($unit->family_id);
        });
    });
});
