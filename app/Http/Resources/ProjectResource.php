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
            'active'                => $this->active,
            'is_approved'           => $this->isApproved(),
            'approval_requested_at' => $this->approval_requested_at,
            'created_at'            => $this->created_at,
            'members_count'         => $this->whenCounted('members'),
            'families_count'        => $this->whenCounted('families'),
            'units_count'           => $this->whenCounted('units'),
            'is_admin'              => $this->whenPivotLoaded('memberships', fn () => (bool) $this->pivot->is_admin),
        ];
    }
}
