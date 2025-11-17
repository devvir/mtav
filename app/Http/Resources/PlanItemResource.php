<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

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
