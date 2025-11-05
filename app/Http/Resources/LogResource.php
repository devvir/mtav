<?php

namespace App\Http\Resources;

use App\Models\User;
use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;

class LogResource extends JsonResource
{
    use ResourceSubsets;
    use WithResourceAbilities;

    public function toArray(Request $_): array
    {
        $user = $this->whenLoaded('user');

        return [
            'id' => $this->id,
            'event' => $this->event,
            'creator' => $user?->name ?? __('System'),
            'creator_href' => $user ? $this->getCreatorHref($user) : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'created_ago' => $this->created_at->diffForHumans(),

            ...$this->relationsData(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'user' => $this->whenLoaded('user', default: ['id' => $this->user_id]),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
        ];
    }

    protected function getCreatorHref(User $user): string
    {
        $routeName = $user->isAdmin() ? 'admins.show' : 'members.show';

        return route($routeName, $user->id);
    }
}
