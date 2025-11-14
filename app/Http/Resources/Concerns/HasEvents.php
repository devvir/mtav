<?php

namespace App\Http\Resources\Concerns;

trait HasEvents
{
    public function sharedEventsData(): array
    {
        /** Auto-set upcomingEvents relation, if events relation is loaded */
        $this->resource->deriveRelation(
            from: 'events',
            derive: 'upcomingEvents',
            using: fn ($events) => $events->where('end_date', '>', now()),
        );

        return [
            'events'                => $this->whenLoaded('events'),
            'events_count'          => $this->whenCountedOrLoaded('events'),
            'upcoming_events'       => $this->whenLoaded('upcomingEvents'),
            'upcoming_events_count' => $this->whenCountedOrLoaded('upcomingEvents'),
        ];
    }
}
