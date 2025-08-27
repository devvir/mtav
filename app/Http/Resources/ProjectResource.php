<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    use WithResourceAbilities;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $_): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'admins' => $this->whenLoaded('admins'),
            'admins_count' => $this->whenCounted('admins'),

            'members' => $this->whenLoaded('members'),
            'members_count' => $this->whenCounted('members'),

            'families' => $this->whenLoaded('families'),
            'families_count' => $this->whenCounted('families'),
        ];
    }
}
