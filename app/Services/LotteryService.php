<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use App\Services\Lottery\PreferencesService;
use App\Services\Lottery\Exceptions\LockedLotteryException;
use App\Services\Lottery\ExecutionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class LotteryService
{
    public function __construct(
        private PreferencesService $preferencesService,
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
        return $this->preferencesService->preferences($family);
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
        $this->preferencesService->updatePreferences($family, $preferences);
    }

    /**
     * Execute Lottery and assign all Units to Families.
     *
     * @param array<string> $options  User-confirmed options
     *
     * @throws Lottery\Exceptions\CannotExecuteLotteryException
     * @throws Lottery\Exceptions\LotteryExecutionException
     * @throws Lottery\Exceptions\LotteryRequiresConfirmationException
     */
    public function execute(Event $lottery, array $options = []): void
    {
        $this->executionService->execute($lottery, $options);
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
