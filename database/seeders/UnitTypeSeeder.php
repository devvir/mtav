<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\UnitType;
use Database\Factories\UnitTypeFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class UnitTypeSeeder extends Seeder
{
    public function run(): void
    {
        Project::all()->each(function (Project $project) {
            $types = collect(UnitTypeFactory::TYPES)->random(rand(3, 5));

            UnitType::factory()
                ->count($types->count())
                ->inProject($project)
                ->sequence(fn (Sequence $sequence) => [
                    'name'        => $types->get($sequence->index)[0],
                    'description' => $types->get($sequence->index)[1],
                ])
                ->create();
        });
    }
}
