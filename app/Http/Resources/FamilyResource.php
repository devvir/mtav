<?php

namespace App\Http\Resources;

use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Family
 */
class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'avatar'        => $this->resource->avatar_url,
            'unit_type'     => $this->whenLoaded('unitType', fn () => ['id' => $this->unitType->id, 'name' => $this->unitType->name]),
            'unit'          => $this->whenLoaded('unit', fn () => $this->unit ? ['id' => $this->unit->id, 'identifier' => $this->unit->identifier] : null),
            'members'       => MemberResource::collection($this->whenLoaded('members')),
            'members_count' => $this->whenCounted('members'),
            'can'           => [
                'update' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
            ],
        ];
    }
}
