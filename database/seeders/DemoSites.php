<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\PlanService;

/**
 * The demo projects' sites, drawn as an admin would: the land, each building floor by floor
 * with its units, and what lies around them (green areas, streets, parking, …). Units are
 * created here, where they stand.
 */
class DemoSites
{
    /** Between a building's outline and its units, and between units */
    private const INSET = 6;

    private const GAP = 4;

    public function __construct(private PlanService $plans)
    {
    }

    /**
     * Las Acacias: two blocks and four houses around a square, on irregular land along an
     * avenue. Block A, three floors of six (two of 1 bedroom, four of 2); block B, a row of
     * five duplexes on two floors (3 bedrooms); four houses with gardens (4 bedrooms).
     *
     * @param list<UnitType> $types  1, 2, 3 and 4 bedrooms
     */
    public function acacias(Project $project, array $types): void
    {
        [$one, $two, $three, $four] = $types;
        $this->land($project, [[40, 70], [610, 30], [960, 120], [945, 600], [520, 650], [55, 600]]);

        // Green first: the buildings stand on it
        $this->shapes($project, [
            ['green', 'Plaza', [[420, 95], [600, 82], [612, 280], [428, 296]]],
            ['green', 'Jardines', [[90, 400], [450, 392], [458, 580], [100, 570]]],
            ['green', null, [[790, 400], [930, 395], [925, 560], [800, 575]]],
            ['green', null, [[70, 280], [400, 276], [402, 318], [72, 322]]],
            ['green', null, [[620, 272], [925, 262], [928, 318], [624, 322]]],
            ['green', null, [[52, 100], [78, 98], [80, 268], [54, 270]]],
        ]);
        foreach ([0, 1, 2] as $floor) {
            $this->building($project, 'Bloque A', 'A', [84, 104, 312, 162], $floor, 3, [$one, $one, $two, $two, $two, $two]);
        }
        foreach ([0, 1] as $floor) {
            $this->building($project, 'Bloque B', 'B', [630, 140, 300, 120], $floor, 5, array_fill(0, 5, $three));
        }
        foreach (range(1, 4) as $n) {
            $this->unit($project, $four, "Casa {$n}", $this->rect(25 + $n * 85, 425, 70, 80), 0);
        }
        $this->shapes($project, [
            ['playground', 'Juegos', $this->rect(560, 540, 120, 56)],
            ['common', 'Salón comunal', $this->rect(810, 425, 100, 110)],
            ['parking', 'Estacionamiento', $this->rect(560, 405, 200, 120)],
            ['street', null, [[60, 330], [940, 330], [940, 372], [60, 372]]],
            ['street', null, $this->rect(478, 372, 44, 270)],
            // Pointing in from the avenue
            ['entrance', 'Entrada', [[500, 598], [530, 642], [470, 642]]],
            ['street', 'Av. Italia', [[20, 650], [980, 600], [985, 652], [25, 702]]],
        ]);
    }

    /**
     * Los Ceibos: two blocks on a courtyard. The north one, two floors of four (2
     * bedrooms); the south one, four accessible units on its ground floor and three of 3
     * bedrooms on each of the two above.
     *
     * @param list<UnitType> $types  2 bedrooms, 3 bedrooms, 2 bedrooms accessible
     */
    public function ceibos(Project $project, array $types): void
    {
        [$two, $three, $accessible] = $types;
        $this->land($project, [[60, 60], [900, 90], [940, 640], [80, 660]]);

        $this->shapes($project, [
            ['green', 'Patio', [[120, 262], [530, 255], [538, 560], [130, 575]]],
            ['green', 'Arboleda', [[76, 110], [100, 108], [104, 560], [84, 565]]],
            ['green', null, [[600, 596], [905, 588], [908, 628], [602, 638]]],
        ]);
        foreach ([0, 1] as $floor) {
            $this->building($project, 'Bloque Norte', 'N', [110, 100, 420, 120], $floor, 4, array_fill(0, 4, $two));
        }
        $this->building($project, 'Bloque Sur', 'S', [600, 380, 300, 200], 0, 2, array_fill(0, 4, $accessible));
        foreach ([1, 2] as $floor) {
            $this->building($project, 'Bloque Sur', 'S', [600, 380, 300, 200], $floor, 3, array_fill(0, 3, $three));
        }
        $this->shapes($project, [
            ['playground', 'Juegos', $this->rect(170, 320, 130, 90)],
            ['common', 'Salón de usos múltiples', $this->rect(380, 430, 120, 95)],
            ['parking', 'Estacionamiento', $this->rect(600, 130, 260, 120)],
            ['street', null, [[550, 262], [586, 262], [586, 648], [550, 650]]],
            ['entrance', 'Entrada', [[568, 604], [590, 646], [546, 646]]],
            ['street', 'Camino Maldonado', [[40, 668], [960, 648], [965, 700], [45, 712]]],
        ]);
    }

    /** Units of a type, where the plan puts new ones (left for its admins to draw) */
    public function undrawn(Project $project, UnitType $type, string $prefix, int $count): void
    {
        foreach (range(1, $count) as $n) {
            $unit = Unit::factory()->create(['project_id' => $project->id, 'unit_type_id' => $type->id, 'identifier' => sprintf('%s-%02d', $prefix, $n)]);
            $this->plans->place($unit);
        }
    }

    private function land(Project $project, array $polygon): void
    {
        $project->plan->update(['width' => 1000, 'height' => 720, 'polygon' => $polygon]);
    }

    /**
     * A floor of a building: its outline, and its units within, row by row, named by
     * floor and place ("A-102": block A, first floor, second unit)
     *
     * @param array{0: int, 1: int, 2: int, 3: int} $footprint  x, y, width, height
     * @param list<UnitType>                       $types      one per unit, in order
     */
    private function building(Project $project, string $name, string $prefix, array $footprint, int $floor, int $columns, array $types): void
    {
        [$x, $y, $width, $height] = $footprint;
        $rows = (int) ceil(count($types) / $columns);
        $project->plan->items()->create(['type' => 'building', 'name' => $name, 'floor' => $floor, 'polygon' => $this->rect($x, $y, $width, $height)]);

        $cellWidth = ($width - 2 * self::INSET - ($columns - 1) * self::GAP) / $columns;
        $cellHeight = ($height - 2 * self::INSET - ($rows - 1) * self::GAP) / $rows;
        foreach ($types as $index => $type) {
            [$row, $column] = [intdiv($index, $columns), $index % $columns];
            $this->unit($project, $type, sprintf('%s-%d%02d', $prefix, $floor, $index + 1), $this->rect(
                $x + self::INSET + $column * ($cellWidth + self::GAP),
                $y + self::INSET + $row * ($cellHeight + self::GAP),
                $cellWidth,
                $cellHeight,
            ), $floor);
        }
    }

    private function unit(Project $project, UnitType $type, string $identifier, array $polygon, int $floor): void
    {
        $unit = Unit::factory()->create(['project_id' => $project->id, 'unit_type_id' => $type->id, 'identifier' => $identifier]);
        $this->plans->place($unit, $polygon, $floor);
    }

    /** @param array<array{0: string, 1: ?string, 2: array}> $shapes  type, name, polygon */
    private function shapes(Project $project, array $shapes): void
    {
        foreach ($shapes as [$type, $name, $polygon]) {
            $project->plan->items()->create(['type' => $type, 'name' => $name, 'floor' => 0, 'polygon' => $polygon]);
        }
    }

    private function rect(float $x, float $y, float $width, float $height): array
    {
        [$left, $top, $right, $bottom] = array_map(fn ($value) => (int) round($value), [$x, $y, $x + $width, $y + $height]);

        return [[$left, $top], [$right, $top], [$right, $bottom], [$left, $bottom]];
    }
}
