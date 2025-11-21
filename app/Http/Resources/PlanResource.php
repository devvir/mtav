<?php

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * @property-read Plan $resource
 *
 * @mixin Plan
 */
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
