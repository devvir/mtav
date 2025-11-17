<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PlanResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'polygon'     => $this->polygon,
            'width'       => $this->width,
            'height'      => $this->height,
            'unit_system' => $this->unit_system,

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),

            'items'       => $this->whenLoaded('items'),
            'items_count' => $this->whenCountedOrLoaded('items'),
        ];
    }
}
