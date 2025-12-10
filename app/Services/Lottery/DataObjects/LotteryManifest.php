<?php

namespace App\Services\Lottery\DataObjects;

use App\Models\Event;
use App\Models\Project;
use App\Services\LotteryService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Complete lottery manifest for a project.
 *
 * Full inventory of unit types, families, and preferences for a project's lottery execution.
 */
class LotteryManifest
{
    /**
     * Complete lottery data grouped by unit type.
     *
     * @var array Lottery data grouped by unit type: [
     *     unitTypeId1 => [
     *       'families' => [
     *         familyId1 => [unitId3, unitId1, unitId5, ...], // sorted preferences
     *         familyId2 => [unitId1, unitId3, unitId2, ...],
     *       ],
     *       'units' => [unitId1, unitId2, unitId3, ...], // unsorted
     *     ],
     *     unitTypeId2 => [...],
     *   ]
     */
    public readonly array $data;

    public readonly int $projectId;
    public readonly int $lotteryId;

    /**
     * @param array<string> $options  User-confirmed options
     */
    public function __construct(
        public readonly string $uuid,
        Event $lottery,
        public readonly array $options = []
    ) {
        $this->lotteryId = $lottery->id;
        $this->projectId = $lottery->project->id;

        $this->data = $this->buildFromProject($lottery->project);
    }

    /**
     * Transform project entities into low-level data structure.
     */
    protected function buildFromProject(Project $project): array
    {
        $lotteryService = app(LotteryService::class);

        $families = $project->families()->with('unitType', 'preferences')->get();

        foreach ($families->groupBy('unit_type_id') as $unitTypeId => $typeFamilies) {
            $data[$unitTypeId] = [
                'families' => $this->buildFamilyPreferences($typeFamilies, $lotteryService),
                'units'    => $this->buildUnitsList($unitTypeId, $project),
            ];
        }

        return $data ?? [];
    }

    /**
     * Build family preferences map for a group of families.
     *
     * @return array Map of family_id => [sorted unit_ids]
     */
    protected function buildFamilyPreferences(Collection $families, LotteryService $lotteryService): array
    {
        foreach ($families as $family) {
            $preferences[$family->id] = $lotteryService->preferences($family)->pluck('id')->toArray();
        }

        return $preferences ?? [];
    }

    /**
     * Build list of unit IDs for a unit type.
     *
     * @return array Unsorted unit IDs
     */
    protected function buildUnitsList(int $unitTypeId, Project $project): array
    {
        return $project->units()->where('unit_type_id', $unitTypeId)->pluck('id')->toArray();
    }
}
