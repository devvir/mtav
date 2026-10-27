<?php

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'polygon'     => $this->polygon,
            'width'       => $this->width,
            'height'      => $this->height,
            'unit_system' => $this->unit_system,
            'items'       => $this->items->map(fn ($item) => [
                'id'       => $item->id,
                'type'     => $item->type,
                'floor'    => $item->floor,
                'polygon'  => $item->polygon,
                'name'     => $item->name,
                'metadata' => $item->metadata,
                'unit'     => $item->unit ? [
                    'id'           => $item->unit->id,
                    'identifier'   => $item->unit->identifier,
                    'unit_type_id' => $item->unit->unit_type_id,
                    'family'       => $item->unit->family ? ['id' => $item->unit->family->id, 'name' => $item->unit->family->name] : null,
                ] : null,
            ]),
        ];
    }
}
