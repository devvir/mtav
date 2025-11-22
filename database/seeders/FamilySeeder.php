<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\UnitType;
use Illuminate\Database\Seeder;

class FamilySeeder extends Seeder
{
    public function run(): void
    {
        UnitType::withCount('units')->get()->each(
            fn (UnitType $type) => Family::factory()
                ->count(max(0, $type->units_count + rand(-1, 1)))
                ->inProject($type->project_id)
                ->create(['unit_type_id' => $type->id])
        );
    }
}
