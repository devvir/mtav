<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->commonResourceData(),

            'type'         => $this->type->value,
            'title'        => $this->title,
            'description'  => $this->description,
            'location'     => $this->location,
            'start_date'   => $this->start_date?->translatedFormat('M j, Y g:i A'),
            'end_date'     => $this->end_date?->translatedFormat('M j, Y g:i A'),
            'is_published' => $this->is_published,
            'allows_rsvp'  => $this->allowsRsvp(),

            'accepted' => $this->whenLoaded('rsvps', fn () => $this->acknowledgedByMe($request, true)),
            'rejected' => $this->whenLoaded('rsvps', fn () => $this->acknowledgedByMe($request, false)),

            'type_label' => $this->type->label(),
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
