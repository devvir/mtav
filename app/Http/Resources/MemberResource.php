<?php

namespace App\Http\Resources;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;

class MemberResource extends UserResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        $this->fillDerivedRsvpRelations();

        return [
            'project' => $this->whenLoaded('projects', fn () => $this->projects->first()),
            'family'  => $this->whenLoaded('family', default: ['id' => $this->family_id]),

            /** RSVPs */
            'rsvps'                     => $this->whenLoaded('rsvps'),
            'rsvps_count'               => $this->whenCountedOrLoaded('rsvps'),
            'upcoming_rsvps'            => $this->whenLoaded('upcomingRsvps'),
            'upcoming_rsvps_count'      => $this->whenCountedOrLoaded('upcomingRsvps'),
            'acknowledged_events'       => $this->whenLoaded('acknowledgedEvents'),
            'acknowledged_events_count' => $this->whenCountedOrLoaded('acknowledgedEvents'),
            'accepted_events'           => $this->whenLoaded('acceptedEvents'),
            'accepted_events_count'     => $this->whenCountedOrLoaded('acceptedEvents'),
            'declined_events'           => $this->whenLoaded('declinedEvents'),
            'declined_events_count'     => $this->whenCountedOrLoaded('declinedEvents'),
        ];
    }

    /**
     * Prepare derived relations from loaded base data.
     */
    protected function fillDerivedRsvpRelations(): void
    {
        /** @var Member $resource */
        $member = $this->resource;

        collect([ /** from 'rsvps', derive => using */
            'upcomingRsvps'      => fn ($rsvps) => $rsvps->where('end_date', '>', now()),
            'acknowledgedEvents' => fn ($rsvps) => $rsvps->whereNotNull('pivot.status'),
            'acceptedEvents'     => fn ($rsvps) => $rsvps->where('pivot.status', true),
            'declinedEvents'     => fn ($rsvps) => $rsvps->where('pivot.status', false),
        ])->each(fn (Closure $using, string $derive) => $member->deriveRelation(
            from: 'rsvps',
            derive: $derive,
            using: $using,
        ));
    }
}
