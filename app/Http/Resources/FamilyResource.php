<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\HasMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FamilyResource extends JsonResource
{
    use HasMedia;

    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'name'   => $this->name,
            'avatar' => $this->resolveAvatar(),

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'members'       => $this->whenLoaded('members'),
            'members_count' => $this->whenCountedOrLoaded('members'),

            'project'   => $this->whenLoaded('project', default: ['id' => $this->project_id]),
            'unit_type' => $this->whenLoaded('unitType', default: ['id' => $this->unit_type_id]),

            ...$this->sharedMediaData(),
        ];
    }

    private function resolveAvatar(): string
    {
        return $this->avatar
            ? Storage::url($this->avatar)
            : "https://api.dicebear.com/9.x/identicon/svg?seed={$this->name}";
    }
}
