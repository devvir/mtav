<?php

namespace App\Http\Resources;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @property-read Log $resource
 *
 * @mixin Log
 */
class LogResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),
            'event'        => $this->event,
            'created_by'   => $this->creator?->fullname ?? __('System'),
            'creator_href' => $this->creator ? $this->getCreatorHref($this->creator) : null,

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'creator' => $this->whenLoaded('creator', default: ['id' => $this->creator_id]),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
        ];
    }

    protected function getCreatorHref(User $user): string
    {
        $routeName = $user->isAdmin() ? 'admins.show' : 'members.show';

        return route($routeName, $user);
    }
}
