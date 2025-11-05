<?php

namespace App\Services;

use App\Events\UserRegistration;
use App\Models\Admin;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class InvitationService
{
    /**
     * Create and invite a new admin.
     */
    public function inviteAdmin(array $userData, array $projectIds): Admin
    {
        $token = $this->createToken();
        $data = ['password' => $token, 'is_admin' => true] + $userData;

        $admin = DB::transaction(function () use ($data, $projectIds) {
            $admin = Admin::create($data);
            $admin->projects()->attach($projectIds);

            return $admin;
        });

        event(new UserRegistration($admin, $token));

        return $admin;
    }

    /**
     * Create and invite a new member.
     */
    public function inviteMember(array $userData, int $projectId): Member
    {
        $token = $this->createToken();
        $data = ['password' => $token] + $userData;

        $member = DB::transaction(
            fn () => Member::create($data)->joinProject($projectId)
        );

        event(new UserRegistration($member, $token));

        return $member;
    }

    /**
     * Complete the user's registration.
     */
    public function completeRegistration(User $user, string $password, array $data): User
    {
        $avatar = isset($data['avatar']) ? $this->storeAvatar($data['avatar']) : null;

        $user->update([
            'password' => $password,
            'avatar' => $avatar,
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
            ...Arr::only($data, ['firstname', 'lastname', 'phone', 'legal_id']),
        ]);

        return $user;
    }

    /**
     * Accepts either the resolved Avatar path or a FileUpload to process.
     */
    protected function storeAvatar(string|UploadedFile $avatar): ?string
    {
        return is_string($avatar) ? $avatar : $avatar->store('avatars', 'public');
    }

    /**
     * Create a new random invitation token.
     */
    protected function createToken(): string
    {
        return base64_encode(random_bytes(32));
    }
}
