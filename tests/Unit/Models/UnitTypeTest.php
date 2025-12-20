// Copilot - Pending review
<?php

use App\Models\Family;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Collection;

describe('UnitType Model Relations', function () {
    it('belongs to a project', function () {
        $unitType = UnitType::find(1); // UnitType #1 from universe

        expect($unitType->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe(1);
    });

    it('has many units', function () {
        $unitType = UnitType::find(2); // UnitType #2 has units

        expect($unitType->units)
            ->toBeInstanceOf(Collection::class)
            ->not->toBeEmpty();
    });

    it('has many families', function () {
        $unitType = UnitType::find(1); // UnitType #1 has families

        expect($unitType->families)
            ->toBeInstanceOf(Collection::class)
            ->not->toBeEmpty();
    });
});

describe('UnitType Model Scopes', function () {
    it('filters unit types alphabetically by name', function () {
        // Create types with specific names for testing
        $type1 = UnitType::factory()->create(['name' => 'Zebra Type']);
        $type2 = UnitType::factory()->create(['name' => 'Alpha Type']);
        $type3 = UnitType::factory()->create(['name' => 'Beta Type']);

        $alphabetical = UnitType::alphabetically()->get()->filter(
            fn ($t) => in_array($t->id, [$type1->id, $type2->id, $type3->id])
        );

        expect($alphabetical->first()->name)->toBe('Alpha Type')
            ->and($alphabetical->last()->name)->toBe('Zebra Type');
    });

    it('searches unit types by name', function () {
        $unitType = UnitType::find(1);

        $results = UnitType::search($unitType->name)->get();

        expect($results->pluck('id'))->toContain($unitType->id);
    });
});
