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
            'avatar' => $this->resolveAvatar(),
            'is_verified' => (bool) $this->email_verified_at,
            'is_admin' => $this->isAdmin(),
            'is_superadmin' => $this->isSuperAdmin(),
            'legal_id' => $this->legal_id ?? '',
            'created_at' => $this->created_at->toDateTimeString(),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),

            ...$this->relationsData(),
        ];
    }

    private function relationsData(): array
    {
        return [
            'projects' => $this->whenLoaded('projects'),
        ];
    }

    private function resolveAvatar(): string
    {
        // $encodedName = base64_encode($this->fullName);
        // $urlEncodedName = urlencode($this->fullName);

        return $this->avatar
            ?? "https://i.pravatar.cc/64?u={$this->email}";
        // ?? "https://avi.avris.it/letter-64/{$encodedName}.png";
        // ?? "https://ui-avatars.com/api/?name={$urlEncodedName}&background=random&rounded=true";
    }
}
