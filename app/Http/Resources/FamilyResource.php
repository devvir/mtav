<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    use WithResourceAbilities;
    use ResourceSubsets;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $_): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'avatar'        => $this->resolveAvatar($this->name),
            'created_at'    => $this->created_at->toDateTimeString(),
            'created_ago'   => $this->created_at->diffForHumans(),

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'members' => $this->whenLoaded('members'),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
        ];
    }

    private function resolveAvatar($name): string
    {
        $urlEncodedName = urlencode($name);

        return $this->avatar
            ?? "https://ui-avatars.com/api/?name={$urlEncodedName}&background=random";
    }
}
