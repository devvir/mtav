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
    public function inviteAdmin(array $input, array $projectIds): Admin
    {
        $data = [
            ...Arr::only($input, ['email', 'firstname', 'lastname']),
            'password' => $this->createToken(),
            'is_admin' => true,
        ];

        $admin = DB::transaction(fn () => tap(
            Admin::create($data),
            fn (Admin $admin) => $admin->projects()->attach($projectIds)
        ));

        event(new UserRegistration($admin, $data['password']));

        return $admin;
    }

    /**
     * Create and invite a new member.
     */
    public function inviteMember(array $input, int $projectId): Member
    {
        $data = [
            ...Arr::only($input, ['family_id', 'email', 'firstname', 'lastname']),
            'password' => $this->createToken(),
        ];

        $member = DB::transaction(
            fn () => Member::create($data)->joinProject($projectId)
        );

        event(new UserRegistration($member, $data['password']));

        return $member;
    }

    /**
     * Complete the user's registration.
     */
    public function completeRegistration(User $user, string $password, array $data): User
    {
        $avatar = isset($data['avatar']) ? $this->storeAvatar($data['avatar']) : null;

        $user->update([
            'password'               => $password,
            'avatar'                 => $avatar,
            'invitation_accepted_at' => now(),
            'email_verified_at'      => now(),
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
