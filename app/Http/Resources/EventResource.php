<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * @property-read Event $resource
 * @mixin Event
 */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->commonResourceData(),

            'type'         => $this->type->value,
            'type_label'   => $this->type->label(),
            'status'       => $this->status,
            'title'        => $this->title,
            'description'  => $this->description,
            'location'     => $this->location,
            'start_date'   => $this->start_date?->translatedFormat('M j, Y g:i A'),
            'end_date'     => $this->end_date?->translatedFormat('M j, Y g:i A'),
            'is_published' => $this->is_published,
            'allows_rsvp'  => $this->allowsRsvp(),

            'start_date_raw' => $this->start_date?->translatedFormat('Y-m-d\TH:i'),
            'end_date_raw'   => $this->end_date?->translatedFormat('Y-m-d\TH:i'),

            'accepted' => $this->whenLoaded('rsvps', fn () => $this->acknowledgedByMe($request, true)),
            'declined' => $this->whenLoaded('rsvps', fn () => $this->acknowledgedByMe($request, false)),

            'is_lottery' => $this->isLottery(),
            'is_online'  => $this->isOnline(),
            'is_onsite'  => $this->isOnSite(),

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'creator'     => $this->whenLoaded('creator', default: ['id' => $this->creator_id]),
            'project'     => $this->whenLoaded('project', default: ['id' => $this->project_id]),
            'rsvps'       => $this->whenLoaded('rsvps'),
            'rsvps_count' => $this->whenCountedOrLoaded('rsvps'),

            'accepted_count' => $this->whenLoaded(
                'rsvps',
                fn () => $this->rsvps->where('pivot.status', true)->count()
            ),
            'declined_count' => $this->whenLoaded(
                'rsvps',
                fn () => $this->rsvps->where('pivot.status', false)->count()
            ),
        ];
    }

    protected function acknowledgedByMe(Request $request, bool $status)
    {
        $currentUser = $request->user();

        return $this->rsvp && $currentUser?->isMember() && $this->rsvps->where([
            'user_id'      => $currentUser->id,
            'pivot.status' => $status,
        ])->isNotEmpty();
    }
}
