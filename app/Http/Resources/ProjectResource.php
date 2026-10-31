<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'organization'          => $this->organization,
            'listed'                => $this->listed,
            'open_to_join'          => $this->open_to_join,
            'allows_guests'         => $this->allows_guests,
            'features'              => $this->features(),
            'is_approved'           => $this->isApproved(),
            'approval_requested_at' => $this->approval_requested_at,
            'created_at'            => $this->created_at,
            'members_count'         => $this->whenCounted('members'),
            'families_count'        => $this->whenCounted('families'),
            'units_count'           => $this->whenCounted('units'),
            'is_admin'              => (bool) $request->user()?->manages($this->resource),
        ];
    }
}
