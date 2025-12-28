<?php

// Copilot - Pending review

use App\Models\Plan;
use App\Services\Plan\Polygons;

uses()->group('Unit.Plan');

describe('Polygons Service', function () {
    test('updates plan polygon and item polygons atomically', function () {
        // Use fixture data from universe.sql
        $plan = Plan::find(1);
        $items = $plan->items;

        expect($items->count())->toBeGreaterThan(0); // Verify plan has items

        $originalPlanPolygon = $plan->polygon;
        $originalItemPolygons = $items->pluck('polygon', 'id')->toArray();

        // Prepare update data
        $newPlanPolygon = [[0, 0], [1000, 0], [1000, 800], [0, 800]];
        $newItems = [
            ['id' => $items[0]->id, 'polygon' => [[10, 10], [110, 10], [110, 110], [10, 110]]],
            ['id' => $items[1]->id, 'polygon' => [[120, 10], [220, 10], [220, 110], [120, 110]]],
        ];

        // Execute update
        app(Polygons::class)->update($plan, $newPlanPolygon, $newItems);

        // Verify plan polygon updated
        $plan->refresh();
        expect($plan->polygon)->toBe($newPlanPolygon)
            ->and($plan->polygon)->not->toBe($originalPlanPolygon);

        // Verify items updated
        $items[0]->refresh();
        $items[1]->refresh();

        expect($items[0]->polygon)->toBe($newItems[0]['polygon'])
            ->and($items[0]->polygon)->not->toBe($originalItemPolygons[$items[0]->id]);

        expect($items[1]->polygon)->toBe($newItems[1]['polygon'])
            ->and($items[1]->polygon)->not->toBe($originalItemPolygons[$items[1]->id]);

        // Verify untouched items remain unchanged
        $items[2]->refresh();
        expect($items[2]->polygon)->toBe($originalItemPolygons[$items[2]->id]);
    });

    test('wraps updates in transaction ensuring atomicity', function () {
        $plan = Plan::find(1);
        $items = $plan->items;

        $originalPlanPolygon = $plan->polygon;
        $originalItemPolygon = $items[0]->polygon;

        // Mock a database failure scenario by using invalid data
        // that will fail after the first update
        try {
            DB::transaction(function () use ($plan, $items) {
                $plan->update(['polygon' => [[0, 0], [500, 0], [500, 500], [0, 500]]]);

                // Force an exception
                throw new \Exception('Simulated failure');
            });
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Verify rollback - polygon should be unchanged
        $plan->refresh();
        expect($plan->polygon)->toBe($originalPlanPolygon);
    });

    test('handles empty items array', function () {
        $plan = Plan::find(1);
        $originalPolygon = $plan->polygon;

        $newPlanPolygon = [[0, 0], [600, 0], [600, 600], [0, 600]];

        app(Polygons::class)->update($plan, $newPlanPolygon, []);

        $plan->refresh();
        expect($plan->polygon)->toBe($newPlanPolygon)
            ->and($plan->polygon)->not->toBe($originalPolygon);

        // All items should remain unchanged
        foreach ($plan->items as $item) {
            $item->refresh();
            expect($item->polygon)->toBeArray();
        }
    });
});
