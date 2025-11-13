<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;

class ProjectResource extends JsonResource
{
    use WithResourceAbilities;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $_): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'created_at' => $this->created_at->translatedFormat('M j, Y g:i A'),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at' => $this->deleted_at?->translatedFormat('M j, Y g:i A'),

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'admins' => $this->whenLoaded('admins'),
            'admins_count' => $this->whenCountedOrLoaded('admins'),

            'members' => $this->whenLoaded('members'),
            'members_count' => $this->whenCountedOrLoaded('members'),

            'families' => $this->whenLoaded('families'),
            'families_count' => $this->whenCountedOrLoaded('families'),

            'unit_types' => $this->whenLoaded('unitTypes'),
            'unit_types_count' => $this->whenCountedOrLoaded('unitTypes'),

            'units' => $this->whenLoaded('units'),
            'units_count' => $this->whenCountedOrLoaded('units'),

            'media' => $this->whenLoaded('media'),
            'media_count' => $this->whenCountedOrLoaded('media'),

            'events' => $this->whenLoaded('events'),
            'events_count' => $this->whenCountedOrLoaded('events'),
        ];
    }
}
