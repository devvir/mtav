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
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'active' => $this->when($request->user()?->isSuperAdmin(), $this->active),
            'created_at' => $this->created_at,
        ];
    }
}
