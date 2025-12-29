<?php

// Copilot - Pending review

use App\Models\Plan;

uses()->group('Feature.Plan');

beforeEach(function () {
    setCurrentProject(1);
});

describe('Update Plan Polygons', function () {
    test('successfully updates plan and item polygons', function () {
        $plan = Plan::find(1);
        $items = $plan->items;

        $payload = [
            'polygon' => [[0, 0], [1000, 0], [1000, 800], [0, 800]],
            'items'   => [
                ['id' => $items[0]->id, 'polygon' => [[10, 10], [110, 10], [110, 110], [10, 110]]],
                ['id' => $items[1]->id, 'polygon' => [[120, 10], [220, 10], [220, 110], [120, 110]]],
            ],
        ];

        $this->sendPatchRequest(['plans.update', $plan->id], $payload, asAdmin: 11, redirects: false)
            ->assertRedirect(route('plans.edit', $plan));

        $plan->refresh();
        expect($plan->polygon)->toBe($payload['polygon']);

        $items[0]->refresh();
        expect($items[0]->polygon)->toBe($payload['items'][0]['polygon']);

        $items[1]->refresh();
        expect($items[1]->polygon)->toBe($payload['items'][1]['polygon']);
    });

    test('validates polygon is required', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'items' => [],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('polygon');
    });

    test('validates polygon is array', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => 'not-an-array',
            'items'   => [],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('polygon');
    });

    test('validates polygon has minimum 3 points', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [100, 0]], // Only 2 points
            'items'   => [],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('polygon');
    });

    test('validates polygon points are coordinate pairs', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [100, 0], [100]], // Last point missing Y
            'items'   => [],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('polygon.2');
    });

    test('validates polygon coordinates are numeric', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], ['abc', 0], [100, 100]],
            'items'   => [],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('polygon.1.0');
    });

    test('validates items is required', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items');
    });

    test('validates items is array', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => 'not-an-array',
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items');
    });

    test('validates item id is required', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [
                ['polygon' => [[10, 10], [110, 10], [110, 110], [10, 110]]],
            ],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items.0.id');
    });

    test('validates item id exists in database', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [
                ['id' => 99999, 'polygon' => [[10, 10], [110, 10], [110, 110], [10, 110]]],
            ],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items.0.id');
    });

    test('validates item belongs to the plan', function () {
        $plan1 = Plan::find(1);
        $plan2 = Plan::find(2);
        $plan2Item = $plan2->items->first();

        $response = $this->sendPatchRequest(['plans.update', $plan1->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [
                ['id' => $plan2Item->id, 'polygon' => [[10, 10], [110, 10], [110, 110], [10, 110]]],
            ],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items');
    });

    test('validates item polygon is required', function () {
        $plan = Plan::find(1);
        $item = $plan->items->first();

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [
                ['id' => $item->id],
            ],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items.0.polygon');
    });

    test('validates item polygon has minimum 3 points', function () {
        $plan = Plan::find(1);
        $item = $plan->items->first();

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [
                ['id' => $item->id, 'polygon' => [[10, 10], [110, 10]]], // Only 2 points
            ],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items.0.polygon');
    });

    test('validates item polygon points are coordinate pairs', function () {
        $plan = Plan::find(1);
        $item = $plan->items->first();

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [
                ['id' => $item->id, 'polygon' => [[10, 10], [110, 10], [110]]], // Last point invalid
            ],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items.0.polygon.2');
    });

    test('validates item polygon coordinates are numeric', function () {
        $plan = Plan::find(1);
        $item = $plan->items->first();

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [
                ['id' => $item->id, 'polygon' => [[10, 10], ['text', 10], [110, 110]]],
            ],
        ], asAdmin: 11);

        expect(inertiaErrors($response))->toHaveKey('items.0.polygon.1.0');
    });

    test('accepts empty items array', function () {
        $plan = Plan::find(1);
        $originalItemPolygons = $plan->items->pluck('polygon', 'id')->toArray();

        $newPolygon = [[0, 0], [1000, 0], [1000, 800], [0, 800]];

        $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => $newPolygon,
            'items'   => [],
        ], asAdmin: 11, redirects: false)->assertRedirect(route('plans.edit', $plan));

        $plan->refresh();
        expect($plan->polygon)->toBe($newPolygon);

        // Items unchanged
        foreach ($plan->items as $item) {
            $item->refresh();
            expect($item->polygon)->toBe($originalItemPolygons[$item->id]);
        }
    });

    test('updates only specified items leaving others unchanged', function () {
        $plan = Plan::find(1);
        $items = $plan->items;

        $originalPolygons = $items->pluck('polygon', 'id')->toArray();

        $payload = [
            'polygon' => [[0, 0], [1000, 0], [1000, 800], [0, 800]],
            'items'   => [
                ['id' => $items[0]->id, 'polygon' => [[10, 10], [110, 10], [110, 110], [10, 110]]],
            ],
        ];

        $this->sendPatchRequest(['plans.update', $plan->id], $payload, asAdmin: 11, redirects: false)
            ->assertRedirect(route('plans.edit', $plan));

        $items[0]->refresh();
        expect($items[0]->polygon)->toBe($payload['items'][0]['polygon']);

        // Other items unchanged
        for ($i = 1; $i < $items->count(); $i++) {
            $items[$i]->refresh();
            expect($items[$i]->polygon)->toBe($originalPolygons[$items[$i]->id]);
        }
    });

    test('requires authentication', function () {
        $plan = Plan::find(1);

        $response = $this->sendPatchRequest(['plans.update', $plan->id], [
            'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items'   => [],
        ], redirects: false);

        $response->assertRedirect(route('login'));
    });
});
