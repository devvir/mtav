<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use WithResourceAbilities;
    use ResourceSubsets;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'firstname'      => $this->firstname,
            'lastname'       => $this->lastname,
            'name'           => trim("{$this->firstname} {$this->lastname}"),
            'is_admin'       => $this->isAdmin(),
            'is_superadmin'  => $this->isSuperAdmin(),
            'family'         => $this->whenLoaded('family', fn () => [
                'id'   => $this->resource->family->id,
                'name' => $this->resource->family->name,
            ]),
        ];
    }
}
