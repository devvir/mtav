<?php

namespace App\Observers;

use App\Models\Conversation;
use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\JoinRequest;
use App\Models\Membership;
use App\Models\User;
use App\Services\ChatService;
use App\Services\FamilyService;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * What a place in a project brings along, however it is given, changed or taken: the
 * project's Community group and its own Assistant (each pinned to begin with, and only while
 * it lasts), its family shown and remembered, and requests it settles.
 */
class MembershipObserver
{
    public function __construct(private ChatService $chat, private FamilyService $families)
    {
    }

    /** A support account reaches every project by its role, and takes no place in any */
    public function creating(Membership $membership): void
    {
        if (User::whereKey($membership->user_id)->where('is_superadmin', true)->exists()) {
            throw new LogicException('A superadmin takes no place in a project');
        }
    }

    /** A family is shown while someone is in it, however they got there */
    public function saving(Membership $membership): void
    {
        if ($membership->family_id && $membership->isDirty('family_id')) {
            Family::onlyTrashed()->find($membership->family_id)?->restore();
        }
    }

    /** Who was ever in a family: it may take them back, and nobody else */
    public function saved(Membership $membership): void
    {
        if ($membership->family_id && ($membership->wasRecentlyCreated || $membership->wasChanged('family_id'))) {
            DB::table('family_people')->insertOrIgnore([
                'family_id'  => $membership->family_id,
                'user_id'    => $membership->user_id,
                'created_at' => now(),
            ]);
        }
    }

    public function created(Membership $membership): void
    {
        // However they got in, what they asked the project is settled by it
        JoinRequest::where('user_id', $membership->user_id)->where('project_id', $membership->project_id)->delete();
        $this->communityGroup($membership)?->participants()->syncWithoutDetaching([$membership->user_id => ['pinned_at' => now()]]);
        // Their own conversation with the Assistant there
        $this->chat->assistantFor($membership->user()->firstOrFail(), $membership->project()->withTrashed()->firstOrFail());
    }

    public function updated(Membership $membership): void
    {
        if ($membership->wasChanged('family_id')) {
            $membership->family_id && $this->closeRequests($membership);
            $this->families->discardIfEmpty(Family::find($membership->getOriginal('family_id')));
        }
    }

    public function deleted(Membership $membership): void
    {
        $this->communityGroup($membership)?->participants()->detach($membership->user_id);
        Conversation::where('project_id', $membership->project_id)->assistantOf($membership->user_id)->delete();
        $this->closeRequests($membership);
        $this->families->discardIfEmpty(Family::find($membership->family_id));
    }

    /** What they were asking no longer stands (a family's decline does) */
    private function closeRequests(Membership $membership): void
    {
        FamilyJoinRequest::pending()->where('user_id', $membership->user_id)->where('project_id', $membership->project_id)->delete();
    }

    private function communityGroup(Membership $membership): ?Conversation
    {
        return Conversation::where('project_id', $membership->project_id)->where('is_community', true)->first();
    }
}
