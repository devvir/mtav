<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type->value,
            'title'        => $this->title,
            'description'  => $this->description,
            'location'     => $this->location,
            'start_date'   => $this->start_date?->translatedFormat('M j, Y g:i A'),
            'end_date'     => $this->end_date?->translatedFormat('M j, Y g:i A'),
            'is_published' => $this->is_published,
            'created_at'   => $this->created_at->translatedFormat('M j, Y g:i A'),
            'created_ago'  => $this->created_at->diffForHumans(),
            'deleted_at'   => $this->deleted_at?->translatedFormat('M j, Y g:i A'),

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
            'creator' => $this->whenLoaded('creator', default: ['id' => $this->creator_id]),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
        ];
    }
}
