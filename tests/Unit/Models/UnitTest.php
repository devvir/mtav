// Copilot - Pending review
<?php

use App\Models\Family;
use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Collection;

describe('Unit Model Relations', function () {
    it('belongs to a project', function () {
        $unit = Unit::find(1); // Unit #1 from universe

        expect($unit->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe(1);
    });

    it('belongs to a unit type', function () {
        $unit = Unit::find(1);

        expect($unit->type)
            ->toBeInstanceOf(UnitType::class);
    });

    it('belongs to a family when assigned', function () {
        $family = Family::find(4);
        $unit = Unit::factory()->create(['family_id' => $family->id]);

        expect($unit->family)
            ->toBeInstanceOf(Family::class)
            ->id->toBe($family->id);
    });

    it('belongs to a plan item when assigned', function () {
        $unit = Unit::find(1); // Unit #1 from universe has plan_item_id = 1

        expect($unit->planItem)
            ->toBeInstanceOf(PlanItem::class)
            ->id->toBe(1);
    });

    it('belongs to a plan through planItem', function () {
        $unit = Unit::find(1); // Unit #1 from universe has plan_item_id = 1
        $planItem = PlanItem::find(1);

        expect($unit->plan)
            ->toBeInstanceOf(Plan::class)
            ->id->toBe($planItem->plan_id);
    });
});

describe('Unit Model Scopes', function () {
    it('filters units alphabetically by identifier', function () {
        // Create units with specific identifiers for testing
        $unit1 = Unit::factory()->create(['identifier' => 'Zebra-1']);
        $unit2 = Unit::factory()->create(['identifier' => 'Alpha-1']);
        $unit3 = Unit::factory()->create(['identifier' => 'Beta-1']);

        $alphabetical = Unit::alphabetically()->get()->filter(
            fn ($u) => in_array($u->id, [$unit1->id, $unit2->id, $unit3->id])
        );

        expect($alphabetical->first()->identifier)->toBe('Alpha-1')
            ->and($alphabetical->last()->identifier)->toBe('Zebra-1');
    });

    it('searches units by identifier', function () {
        $unit = Unit::find(1);

        $results = Unit::search($unit->identifier)->get();

        expect($results->pluck('id'))->toContain($unit->id);
    });
});
