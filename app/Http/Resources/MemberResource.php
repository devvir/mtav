<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A person as seen within a project. Contact details are shown to the project's
 * admins and to the person themselves.
 *
 * @mixin User
 */
class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $project = currentProject();
        $membership = $project ? $this->membershipIn($project) : null;
        // Admins (and the person) see both, and the ID number given; everyone else what the
        // person shares
        $seesAll = $viewer && ($viewer->is($this->resource) || ($project && $viewer->manages($project)));

        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'firstname' => $this->firstname,
            'lastname'  => $this->lastname,
            'avatar'    => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
            'about'     => $this->about,
            'is_admin'  => (bool) $membership?->is_admin,
            'family'    => $membership?->family ? ['id' => $membership->family->id, 'name' => $membership->family->name] : null,
            'joined_at' => $membership?->created_at,
            'email'     => $this->when($seesAll || $this->email_public, $this->email),
            'phone'     => $this->when($seesAll || $this->phone_public, $this->phone),
            'legal_id'  => $this->when($seesAll, $this->legal_id),
        ];
    }
}
