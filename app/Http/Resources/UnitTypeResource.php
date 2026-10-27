<?php

namespace App\Http\Resources;

use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UnitType
 */
class UnitTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'units_count'    => $this->whenCounted('units'),
            'families_count' => $this->whenCounted('families'),
            'units'          => UnitResource::collection($this->whenLoaded('units')),
            'families'       => FamilyResource::collection($this->whenLoaded('families')),
            'can'            => [
                'update' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
            ],
        ];
    }
}
