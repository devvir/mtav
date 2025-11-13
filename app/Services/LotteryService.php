<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Project;
use App\Models\User;

class LotteryService
{
    public function createEvent(Project $project, ?User $creator = null): Event
    {
        return Event::create([
            'project_id'   => $project->id,
            'creator_id'   => $creator?->id,
            'title'        => __('Lottery'),
            'description'  => __('The lottery event determines the allocation of units based on family preferences. This is a fair and transparent process where each family\'s preferences are considered according to the established lottery algorithm.'),
            'type'         => EventType::LOTTERY,
            'is_published' => true,
        ]);
    }
}
