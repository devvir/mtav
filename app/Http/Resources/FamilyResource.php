<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FamilyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $_): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'avatar'      => $this->resolveAvatar(),
            'created_at'  => $this->created_at->translatedFormat('M j, Y g:i A'),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at'  => $this->deleted_at?->translatedFormat('M j, Y g:i A'),

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
        ];
    }

    private function resolveAvatar(): string
    {
        return $this->avatar
            ? Storage::url($this->avatar)
            : "https://api.dicebear.com/9.x/identicon/svg?seed={$this->name}";
    }
}
