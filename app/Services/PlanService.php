<?php

namespace App\Services;

use App\Events\ProjectChanged;
use App\Models\Plan;
use App\Models\PlanItem;
use App\Models\Project;
use App\Models\Unit;
use App\Services\Plan\Defaults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function __construct(private Defaults $defaults)
    {
    }

    /**
     * An empty 800×600 canvas whose boundary is the whole canvas.
     */
    public function createFor(Project $project): Plan
    {
        return $project->plan()->create([
            'polygon'     => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'width'       => 800,
            'height'      => 600,
            'unit_system' => 'meters',
        ]);
    }

    /**
     * Put a new unit on the plan: where it was drawn; else next to the units before it (its
     * type's size, or a plain square). Once the site is drawn, such a unit waits beside the
     * canvas for an admin to place it, rather than land on the drawing. On top of what is
     * already there.
     *
     * @param array<int, array{0: float, 1: float}>|null $polygon
     */
    public function place(Unit $unit, ?array $polygon = null, int $floor = 0): PlanItem
    {
        $plan = $unit->project->plan;

        return $plan->items()->create([
            'unit_id'  => $unit->id,
            'type'     => 'unit',
            'floor'    => $floor,
            'position' => ($plan->items()->max('position') ?? -1) + 1,
            'placed'   => $polygon !== null || ! $plan->items()->whereNull('unit_id')->exists(),
            'polygon'  => $polygon ?? $this->defaults->getNextAvailablePosition($plan, $unit->type->plan_width
                ? [$unit->type->plan_width, $unit->type->plan_height]
                : null),
        ]);
    }

    /**
     * Save the plan as drawn: boundary, size, and every shape in its stack's order (new ones
     * created, missing non-unit ones removed). The ids new shapes got, by the key the editor
     * gave them.
     *
     * @param array{width: float, height: float, polygon: array, items: array} $data
     *
     * @return array<string, int>
     */
    public function update(Plan $plan, array $data): array
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update(collect($data)->only('width', 'height', 'polygon')->all());

            $kept = [];
            $created = [];

            foreach (array_values($data['items']) as $position => $item) {
                $attributes = [
                    'floor'    => $item['floor'],
                    'position' => $position,
                    'placed'   => (bool) ($item['placed'] ?? true),
                    'polygon'  => $item['polygon'],
                    'name'     => $item['name'] ?? null,
                    'metadata' => isset($item['color']) ? ['color' => $item['color']] : null,
                ];

                $existing = isset($item['id']) ? $plan->items()->whereKey($item['id'])->first() : null;

                if ($existing) {
                    // A unit stays a unit; any other shape may change its kind
                    $existing->update($existing->unit_id ? $attributes : [...$attributes, 'type' => $item['type']]);
                    $kept[] = $existing->id;
                } elseif ($item['type'] === 'unit') {
                    // A unit removed meanwhile: its shape goes with it
                    continue;
                } else {
                    $new = $plan->items()->create([...$attributes, 'type' => $item['type']]);
                    $kept[] = $new->id;
                    if (isset($item['key'])) {
                        $created[$item['key']] = $new->id;
                    }
                }
            }

            $plan->items()->whereNull('unit_id')->whereNotIn('id', $kept)->delete();

            DB::afterCommit(fn () => rescue(fn () => ProjectChanged::dispatch($plan->project_id, 'plan', 'updated', $plan->id, Auth::id())));

            return $created;
        });
    }
}
