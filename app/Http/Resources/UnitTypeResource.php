<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UnitTypeResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'name'        => $this->name,
            'description' => $this->description,

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),

            'families'       => $this->whenLoaded('families'),
            'families_count' => $this->whenCountedOrLoaded('families'),

            'units'       => $this->whenLoaded('units'),
            'units_count' => $this->whenCountedOrLoaded('units'),
        ];
    }
}
