<?php

namespace App\Http\Resources;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Unit
 */
class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'identifier' => $this->identifier,
            'type'       => $this->whenLoaded('type', fn () => ['id' => $this->type->id, 'name' => $this->type->name]),
            'family'     => $this->whenLoaded('family', fn () => $this->family ? ['id' => $this->family->id, 'name' => $this->family->name] : null),
            'can'        => [
                'update' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
            ],
        ];
    }
}
