<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminResource extends UserResource
{
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

            'manages' => $this->whenLoaded('projects', fn () => $this->projects),
        ];
    }
}
