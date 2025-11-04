<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FamilyResource extends JsonResource
{
    use ResourceSubsets;
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
            'avatar' => $this->resolveAvatar(),
            'created_at' => $this->created_at->toDateTimeString(),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'members' => $this->whenLoaded('members'),
            'members_count' => $this->whenCounted(
                'members',
                default: fn () => $this->whenLoaded('members', fn () => $this->members->count())),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
            'unit_type' => $this->whenLoaded('unitType', default: ['id' => $this->unit_type_id]),
        ];
    }

    private function resolveAvatar(): string
    {
        return $this->avatar
            ? Storage::url($this->avatar)
            : "https://api.dicebear.com/9.x/identicon/svg?seed=={$this->name}";
    }
}
