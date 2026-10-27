<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invitation
 */
class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'name'       => trim("{$this->firstname} {$this->lastname}") ?: null,
            'is_admin'   => $this->is_admin,
            'family'     => $this->whenLoaded('family', fn () => $this->family ? ['id' => $this->family->id, 'name' => $this->family->name] : null),
            'inviter'    => $this->whenLoaded('inviter', fn () => $this->inviter?->name),
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'can'        => ['delete' => $request->user()?->can('delete', $this->resource)],
        ];
    }
}
