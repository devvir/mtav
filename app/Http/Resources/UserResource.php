<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    use ResourceSubsets;
    use WithResourceAbilities;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fullName = trim($this->firstname.' '.($this->lastname ?? ''));

        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
            'firstname' => $this->firstname ?? '',
            'lastname' => $this->lastname ?? '',
            'name' => $fullName,
            'about' => $this->about ?? null,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'is_admin' => $this->isAdmin(),
            'created_at' => $this->created_at->toDateTimeString(),
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),

            ...$this->relationsData(),

            ...$this->sensitiveData($request),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'projects' => $this->whenLoaded('projects'),
            'projects_count' => $this->whenCountedOrLoaded('projects'),
        ];
    }

    /**
     * Data sent to the frontend only for Admins/Superadmins.
     */
    protected function sensitiveData(Request $request): array
    {
        if (! $request->user()?->isAdmin()) {
            return [];
        }

        return [
            'is_superadmin' => $this->isSuperadmin(),
            'legal_id' => $this->legal_id ?? '',
            'is_verified' => (bool) $this->email_verified_at,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'invitation_accepted_at' => $this->invitation_accepted_at?->toDateTimeString(),
        ];
    }
}
