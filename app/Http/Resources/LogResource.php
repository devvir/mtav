<?php

namespace App\Http\Resources;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @property-read Log $resource
 * @mixin Log
 */
class LogResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        $user = $this->whenLoaded('user');

        return [
            ...$this->commonResourceData(),

            'event'        => $this->event,
            'creator'      => $user?->name ?? __('System'),
            'creator_href' => $user ? $this->getCreatorHref($user) : null,

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'user'    => $this->whenLoaded('user', default: ['id' => $this->user_id]),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
        ];
    }

    protected function getCreatorHref(User $user): string
    {
        $routeName = $user->isAdmin() ? 'admins.show' : 'members.show';

        return route($routeName, $user);
    }
}
