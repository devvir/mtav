<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\HasEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    use HasEvents;

    public function toArray(Request $request): array
    {
        return [
            ...$this->commonResourceData(),

            'email'     => $this->email,
            'phone'     => $this->phone ?? '',
            'firstname' => $this->firstname ?? '',
            'lastname'  => $this->lastname ?? '',
            'name'      => trim($this->firstname . ' ' . ($this->lastname ?? '')),
            'about'     => $this->about ?? null,
            'avatar'    => $this->avatar ? Storage::url($this->avatar) : null,
            'is_admin'  => $this->isAdmin(),

            ...$this->sensitiveData($request),

            /** Relations data */
            'projects'       => $this->whenLoaded('projects'),
            'projects_count' => $this->whenCountedOrLoaded('projects'),

            ...$this->sharedEventsData(),
        ];
    }

    /**
     * Data sent to the frontend only for Admins/Superadmins.
     */
    final protected function sensitiveData(Request $request): array
    {
        if (! $request->user()?->isAdmin()) {
            return [];
        }

        return [
            'is_superadmin'          => $this->isSuperadmin(),
            'legal_id'               => $this->legal_id ?? '',
            'is_verified'            => (bool) $this->email_verified_at,
            'email_verified_at'      => $this->email_verified_at?->translatedFormat('M j, Y g:i A'),
            'invitation_accepted_at' => $this->invitation_accepted_at?->translatedFormat('M j, Y g:i A'),
        ];
    }
}
