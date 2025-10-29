<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
            'unit_type' => $this->whenLoaded('unitType', default: ['id' => $this->unit_type_id]),
        ];
    }

    private function resolveAvatar(): string
    {
        // $urlEncodedName = urlencode($this->name);

        return $this->avatar
            ?? "https://api.dicebear.com/9.x/identicon/svg?seed=={$this->name}";
        // ?? "https://i.pravatar.cc/64?u={$this->name}";
        // ?? "https://ui-avatars.com/api/?name={$urlEncodedName}&background=random";
    }
}
