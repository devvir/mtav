<?php

namespace App\Http\Props;

use App\Models\Project;
use App\Models\User;

/** Who is signed in, as every page's frame shows them, and what their account may do */
class AccountProps
{
    /** @return array<string, mixed> */
    public function of(User $user): array
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'firstname'     => $user->firstname,
            'lastname'      => $user->lastname,
            'email'         => $user->email,
            'avatar'        => $user->avatar_url,
            'is_superadmin' => $user->isSuperadmin(),
            'can_deploy'    => $user->can('deploy'),
            'verified'      => $user->hasVerifiedEmail(),
            // Anyone may start another project, within the limits of those not yet approved
            'can_start_project' => $user->can('create', Project::class),
        ];
    }
}
