<?php

// Copilot - Pending review

use App\Models\Plan;
use App\Models\PlanItem;
use App\Services\PlanService;

uses()->group('Feature.Plan');

describe('PlanService::updatePlan', function () {
    test('updates plan and items correctly', function () {
        setCurrentProject(1);

        $plan = Plan::find(1);
        $items = $plan->items;

        $data = [
            'polygon' => [[0, 0], [900, 0], [900, 700], [0, 700]],
            'items'   => [
                ['id' => $items[0]->id, 'polygon' => [[15, 15], [115, 15], [115, 115], [15, 115]]],
                ['id' => $items[1]->id, 'polygon' => [[125, 15], [225, 15], [225, 115], [125, 115]]],
            ],
        ];

        app(PlanService::class)->updatePlan($plan, $data);

        $plan->refresh();
        expect($plan->polygon)->toBe($data['polygon']);

        $items[0]->refresh();
        expect($items[0]->polygon)->toBe($data['items'][0]['polygon']);

        $items[1]->refresh();
        expect($items[1]->polygon)->toBe($data['items'][1]['polygon']);
    });

    test('updates persist to database', function () {
        setCurrentProject(1);

        $plan = Plan::find(1);
        $item = $plan->items->first();

        $newPolygon = [[0, 0], [950, 0], [950, 750], [0, 750]];
        $newItemPolygon = [[20, 20], [120, 20], [120, 120], [20, 120]];

        $data = [
            'polygon' => $newPolygon,
            'items'   => [
                ['id' => $item->id, 'polygon' => $newItemPolygon],
            ],
        ];

        app(PlanService::class)->updatePlan($plan, $data);

        // Fetch from DB again to verify persistence
        $freshPlan = Plan::find($plan->id);
        $freshItem = PlanItem::find($item->id);

        expect($freshPlan->polygon)->toBe($newPolygon);
        expect($freshItem->polygon)->toBe($newItemPolygon);
    });

    test('handles plan with no item updates', function () {
        setCurrentProject(1);

        $plan = Plan::find(1);
        $originalItemPolygons = $plan->items->pluck('polygon', 'id')->toArray();

        $newPolygon = [[0, 0], [1100, 0], [1100, 850], [0, 850]];

        $data = [
            'polygon' => $newPolygon,
            'items'   => [],
        ];

        app(PlanService::class)->updatePlan($plan, $data);

        $plan->refresh();
        expect($plan->polygon)->toBe($newPolygon);

        // Verify items unchanged
        foreach ($plan->items as $item) {
            $item->refresh();
            expect($item->polygon)->toBe($originalItemPolygons[$item->id]);
        }
    });
});
