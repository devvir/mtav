<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use App\Services\Lottery\ConsistencyService;
use App\Services\Lottery\Exceptions\LockedLotteryException;
use App\Services\Lottery\ExecutionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class LotteryService
{
    public function __construct(
        private ConsistencyService $consistencyService,
        private ExecutionService $executionService
    ) {
        // ...
    }

    public function createLotteryEvent(Project $project, ?User $creator = null): Event
    {
        return Event::create([
            'project_id'   => $project->id,
            'creator_id'   => $creator?->id,
            'title'        => __('Lottery'),
            'description'  => __('general.lottery_default_description'),
            'type'         => EventType::LOTTERY,
            'is_published' => true,
        ]);
    }

    /**
     * Update lottery event details.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws LockedLotteryException if lottery execution has started.
     */
    public function updateLotteryEvent(Event $lottery, array $data): void
    {
        if (! $lottery->is_published) {
            throw new LockedLotteryException();
        }

        $lottery->update(
            Arr::only($data, ['title', 'description', 'start_date'])
        );
    }

    /**
     * Get sorted Unit preferences for a Family.
     *
     * Returns all Units of the Family's UnitType, with preferences first (in order)
     * and then all remaining ones (ordered by ID).
     */
    public function preferences(Family $family): Collection
    {
        $this->consistencyService->sanitizeBeforeFetch($family);

        $family->loadMissing(['preferences', 'unitType.units']);

        // Get remaining units (not yet preferred) in ID order
        $remainingUnits = $family->unitType->units
            ->whereNotIn('id', $family->preferences->pluck('id'))
            ->sortBy('id');

        return $family->preferences->concat($remainingUnits);
    }

    /**
     * Update family unit preferences (replaces existing preferences).
     *
     * Note: $preferences must include all Units of the Family's UnitType.
     *
     * @param array<array{id: int}> $preferences
     */
    public function updatePreferences(Family $family, array $preferences): void
    {
        $this->consistencyService->validateBeforeUpdate($family, $preferences);

        $family->preferences()->sync(
            collect($preferences)->map(fn ($preference, $idx) => [
                'unit_id' => $preference['id'],
                'order'   => $idx + 1,
            ])->keyBy('unit_id')
        );
    }

    /**
     * Execute Lottery and assign all Units to Families.
     *
     * @param  bool  $force  Bypass Unit/Family mismatch validation
     *
     * @throws Lottery\Exceptions\CannotExecuteLotteryException
     * @throws Lottery\Exceptions\LotteryExecutionException
     */
    public function execute(Event $lottery, bool $force = false): void
    {
        $this->executionService->execute($lottery, $force);
    }

    /**
     * Invalidate a partial or completed Lottery execution.
     *
     * Restores lottery, removes assignments, and adds an INVALIDATE audit.
     */
    public function invalidate(Event $lottery): void
    {
        $this->executionService->invalidate($lottery);
    }
}
