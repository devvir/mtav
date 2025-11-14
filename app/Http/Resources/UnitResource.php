<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UnitResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'identifier' => $this->identifier,

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'type'    => $this->whenLoaded('type', default: ['id' => $this->unit_type_id]),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
            'family'  => $this->whenLoaded('family', default: ['id' => $this->family_id]),
        ];
    }
}
