<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;

class UnitTypeResource extends JsonResource
{
    use ResourceSubsets;
    use WithResourceAbilities;

    public function toArray(Request $_): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at->translatedFormat('M j, Y g:i A'),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at' => $this->deleted_at?->translatedFormat('M j, Y g:i A'),

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),

            'families' => $this->whenLoaded('families'),
            'families_count' => $this->whenCountedOrLoaded('families'),

            'units' => $this->whenLoaded('units'),
            'units_count' => $this->whenCountedOrLoaded('units'),
        ];
    }
}
