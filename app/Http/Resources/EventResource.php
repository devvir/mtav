<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'           => $this->id,
            'type'         => $this->type->value,
            'title'        => $this->title,
            'description'  => $this->description,
            'location'     => $this->location,
            'start_date'   => $this->start_date,
            'end_date'     => $this->end_date,
            'status'       => $this->status,
            'is_published' => $this->is_published,
            'allows_rsvp'  => $this->allows_rsvp,
            'creator'      => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'attending'    => $this->whenLoaded('rsvps', fn () => $this->rsvps->firstWhere('id', $user?->id)?->pivot->attending),
            'attendees'    => $this->whenLoaded('rsvps', fn () => $this->rsvps->where('pivot.attending', true)
                ->map(fn ($person) => ['id' => $person->id, 'name' => $person->name])->values()),
            'can' => [
                'update' => $user?->can('update', $this->resource),
                'delete' => $user?->can('delete', $this->resource),
                'rsvp'   => $user?->can('rsvp', $this->resource),
            ],
        ];
    }
}
