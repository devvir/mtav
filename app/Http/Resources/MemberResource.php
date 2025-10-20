<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;

class MemberResource extends UserResource
{
    use ResourceSubsets;
    use WithResourceAbilities;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        return [
            ...$base,

            'project' => $this->whenLoaded('projects', fn () => $this->projects->first()),
            'family' => $this->whenLoaded('family', default: ['id' => $this->family_id]),
        ];
    }
}
