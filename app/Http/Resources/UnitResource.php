<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;

class UnitResource extends JsonResource
{
    use ResourceSubsets;
    use WithResourceAbilities;

    public function toArray(Request $_): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'created_at' => $this->created_at->translatedFormat('M j, Y g:i A'),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at' => $this->deleted_at?->translatedFormat('M j, Y g:i A'),

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'type' => $this->whenLoaded('type', default: ['id' => $this->unit_type_id]),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
            'family' => $this->whenLoaded('family', default: ['id' => $this->family_id]),
        ];
    }
}
