<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * @property-read Event $resource
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'type'        => $this->type->value,
            'type_label'  => $this->type->label(),
            'status'      => $this->status,
            'title'       => $this->title,
            'description' => $this->description,
            'location'    => $this->location,

            ...$this->booleanProps(),
            ...$this->datetimeProps(),

            ...$this->relationsData(),
        ];
    }

    protected function booleanProps(): array
    {
        return [
            'is_published' => $this->isPublished(),
            'allows_rsvp'  => $this->allowsRsvp(),

            'accepted' => $this->whenLoaded('rsvps', fn () => $this->acknowledgedByMe(true)),
            'declined' => $this->whenLoaded('rsvps', fn () => $this->acknowledgedByMe(false)),

            'is_lottery' => $this->isLottery(),
            'is_online'  => $this->isOnline(),
            'is_onsite'  => $this->isOnSite(),
        ];
    }

    protected function datetimeProps(): array
    {
        return [
            'start_date'     => $this->start_date?->translatedFormat('M j, Y g:i A'),
            'end_date'       => $this->end_date?->translatedFormat('M j, Y g:i A'),
            'start_date_raw' => $this->start_date,
            'end_date_raw'   => $this->end_date,
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

    protected function acknowledgedByMe(bool $status)
    {
        $currentUser = request()->user();

        return $this->rsvp && $currentUser?->isMember() && $this->rsvps->where([
            'user_id'      => $currentUser->id,
            'pivot.status' => $status,
        ])->isNotEmpty();
    }
}
