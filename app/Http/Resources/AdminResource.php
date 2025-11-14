<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminResource extends UserResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'manages'       => $this->whenLoaded('projects', fn () => $this->projects),
            'manages_count' => $this->whenCountedOrLoaded('projects'),
        ];
    }
}
