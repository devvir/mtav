<?php

namespace App\Http\Resources;

use App\Models\JoinRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A project as the directory shows it, to anyone, and where the viewer stands with it.
 *
 * @mixin Project
 */
class DirectoryProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'organization'  => $this->organization,
            'description'   => $this->description,
            'members_count' => $this->members_count,
            'units_count'   => $this->units_count,
            'open_to_join'  => $this->open_to_join,
            'standing'      => $this->standing($request),
        ];
    }

    /** Which says what they can do about it */
    private function standing(Request $request): string
    {
        /** @var JoinRequest|null $asked */
        $asked = $this->joinRequests->first();

        return match (true) {
            $request->user()->canAccess($this->resource)  => 'in',
            $this->open_to_join && $this->isInDirectory() => 'can_join',
            (bool) $asked?->isPending()                   => 'asked',
            (bool) $asked?->isSettled()                   => 'declined',
            default                                       => 'can_ask',
        };
    }
}
