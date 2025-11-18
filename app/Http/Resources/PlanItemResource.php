<?php

namespace App\Http\Resources;

use App\Models\PlanItem;
use Illuminate\Http\Request;

/**
 * @property-read PlanItem $resource
 * @mixin PlanItem
 */
class PlanItemResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'name'     => $this->name,
            'type'     => $this->type,
            'polygon'  => $this->polygon,
            'floor'    => $this->floor,
            'metadata' => $this->metadata,

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'plan' => $this->whenLoaded('plan', default: ['id' => $this->plan_id]),
            'unit' => $this->whenLoaded('unit'),
        ];
    }
}
