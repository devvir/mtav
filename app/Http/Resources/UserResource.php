<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use ResourceSubsets;
    use WithResourceAbilities;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $_): array
    {
        $fullName = trim($this->firstname.' '.($this->lastname ?? ''));

        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
            'firstname' => $this->firstname ?? '',
            'lastname' => $this->lastname ?? '',
            'name' => $fullName,
            'avatar' => $this->resolveAvatar($fullName),
            'is_verified' => (bool) $this->email_verified_at,
            'is_admin' => $this->isAdmin(),
            'is_superadmin' => $this->isSuperAdmin(),
            'created_at' => $this->created_at->toDateTimeString(),
            'created_ago' => $this->created_at->diffForHumans(),

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'projects' => $this->whenLoaded('projects'),
        ];
    }

    private function resolveAvatar($fullName): string
    {
        $urlEncodedName = urlencode($fullName);

        return $this->avatar
            ?? "https://ui-avatars.com/api/?name={$urlEncodedName}&background=random&rounded=true";
    }
}
