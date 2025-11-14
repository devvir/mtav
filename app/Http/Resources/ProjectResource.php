<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\HasEvents;
use Illuminate\Http\Request;

class ProjectResource extends JsonResource
{
    use HasEvents;

    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'name'        => $this->name,
            'description' => $this->description,
            'active'      => $this->active,

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'admins'         => $this->whenLoaded('admins'),
            'admins_count'   => $this->whenCountedOrLoaded('admins'),
            'members'        => $this->whenLoaded('members'),
            'members_count'  => $this->whenCountedOrLoaded('members'),
            'families'       => $this->whenLoaded('families'),
            'families_count' => $this->whenCountedOrLoaded('families'),

            'unit_types'       => $this->whenLoaded('unitTypes'),
            'unit_types_count' => $this->whenCountedOrLoaded('unitTypes'),
            'units'            => $this->whenLoaded('units'),
            'units_count'      => $this->whenCountedOrLoaded('units'),

            'media'       => $this->whenLoaded('media'),
            'media_count' => $this->whenCountedOrLoaded('media'),

            ...$this->sharedEventsData(),
        ];
    }
}
