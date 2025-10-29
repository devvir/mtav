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
            'description' => $this->description,
            'active' => $this->active,
            'created_at' => $this->created_at->toDateTimeString(),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'admins' => $this->whenLoaded('admins'),
            'admins_count' => $this->whenCounted(
                'admins',
                default: fn () => $this->whenLoaded('admins', fn () => $this->admins?->count())
            ),

            'members' => $this->whenLoaded('members'),
            'members_count' => $this->whenCounted(
                'members',
                default: fn () => $this->whenLoaded('members', fn () => $this->members?->count())
            ),

            'families' => $this->whenLoaded('families'),
            'families_count' => $this->whenCounted(
                'families',
                default: fn () => $this->whenLoaded('families', fn () => $this->families?->count())
            ),
        ];
    }
}
