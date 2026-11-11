<?php

namespace App\Services;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Project;
use App\Models\User;
use App\Notifications\FamilyRequestNotification;
use App\Notifications\JoinedNotification;
use Illuminate\Validation\ValidationException;

/**
 * A family holds people: it exists while it has someone in it, someone invited to it, or the
 * unit it received. People ask to join one, any of its members answering, and leave it; nothing
 * is ever merged.
 */
class FamilyService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Someone taking part starts their family, and is in it. Under the name of an archived
     * family they were in, that family takes them back, ranking and all (not one that declined
     * them); anyone else gets a new family of that name.
     *
     * @param  array{name: string, unit_type_id: int|string}  $data
     */
    public function start(User $user, Project $project, array $data): Family
    {
        return $project->holdingLottery(function () use ($user, $project, $data) {
            $project->ensureLotteryNotRunning();

            $family = Family::onlyTrashed()->where('project_id', $project->id)->where('name', $data['name'])
                ->whereHas('everyone', fn ($query) => $query->whereKey($user->id))
                ->whereDoesntHave('joinRequests', fn ($query) => $query->declined()->where('user_id', $user->id))
                ->latest('deleted_at')
                ->first();

            $family?->restore();
            $family?->update($data);
            $family ??= $project->families()->create($data);
            $user->membershipIn($project)->update(['family_id' => $family->id]);
            $user->unsetRelation('memberships');

            return $family;
        });
    }

    public function ask(Family $family, User $user): FamilyJoinRequest
    {
        $this->refuseIf((bool) $user->familyIn($family->project)?->is($family), 'family_already_in');
        $this->refuseIf(
            $family->joinRequests()->declined()->where('user_id', $user->id)->exists(),
            'family_declined',
        );
        $this->refuseIf(
            FamilyJoinRequest::pending()->where('user_id', $user->id)->where('project_id', $family->project_id)->exists(),
            'family_request_open',
        );

        $joinRequest = $family->joinRequests()->create(['user_id' => $user->id, 'project_id' => $family->project_id]);

        if (! $this->notifications->firstAskInAWhile($user, "family:{$family->id}")) {
            return $joinRequest;
        }

        $family->members->each(function (User $member) use ($family, $user, $joinRequest) {
            $this->tell($member, NotificationType::FAMILY_REQUESTED, 'family_requested', $family, $user, ['name' => $user->name]);
            $member->notify(new FamilyRequestNotification($joinRequest));
        });

        return $joinRequest;
    }

    public function accept(FamilyJoinRequest $joinRequest, User $by): void
    {
        $family = $joinRequest->family;
        $user = $joinRequest->user;
        $membership = $user->membershipIn($family->project);

        $family->project->holdingLottery(function () use ($joinRequest, $membership, $family, $user) {
            $family->project->ensureLotteryNotRunning();
            $joinRequest->delete();
            $membership->update(['family_id' => $family->id]);
            $user->unsetRelation('memberships');
        });

        $this->tell($user, NotificationType::ADDED_TO_FAMILY, 'added_to_family', $family, $by, [
            'name'    => $by->name,
            'project' => $family->project->name,
        ]);
        $user->notify(new JoinedNotification($membership, $by));
    }

    /** For good, until one of the family removes it: someone either is in this family or is not */
    public function decline(FamilyJoinRequest $joinRequest, User $by): void
    {
        $joinRequest->update(['declined_at' => now()]);

        $this->tell($joinRequest->user, NotificationType::FAMILY_DECLINED, 'family_declined', $joinRequest->family, $by);
    }

    /** Leaving a family for none: with guests they stay as one; admins are in the project anyway */
    public function leave(User $user, Project $project): void
    {
        $membership = $user->membershipIn($project);

        $this->refuseIf(! $membership?->family_id, 'family_none');
        $this->refuseIf(! $project->allows_guests && ! $membership->is_admin, 'family_cannot_leave');

        $project->holdingLottery(function () use ($project, $membership, $user) {
            $project->ensureLotteryNotRunning();
            $membership->update(['family_id' => null]);
            $user->unsetRelation('memberships');
        });
    }

    /**
     * With nobody in it, nobody invited, and no unit, a family has no one to answer for it: it
     * is archived, ranking and all, and its admins are told. Inviting someone brings it back.
     * While a lottery stands, families stay as it found them.
     */
    public function discardIfEmpty(?Family $family): void
    {
        if (! $family || $family->trashed()) {
            return;
        }

        $asking = $family->project->holdingLottery(function () use ($family) {
            $inUse = $family->project->lotteryLocked()
                || $family->memberships()->exists()
                || $family->invitations()->pending()->exists()
                || $family->unit()->exists();

            if ($inUse) {
                return null;
            }

            $asking = $family->joinRequests()->pending()->with('user')->get();
            $asking->each->delete();
            $family->delete();

            return $asking;
        });

        if (! $asking) {
            return;
        }

        $asking->each(fn (FamilyJoinRequest $joinRequest) => $this->tell(
            $joinRequest->user,
            NotificationType::FAMILY_DISCARDED,
            'family_gone',
            $family,
            url: route('family.create', $family->project_id),
        ));
        $family->project->admins->each(fn (User $admin) => $this->tell(
            $admin,
            NotificationType::FAMILY_DISCARDED,
            'family_discarded',
            $family,
            url: route('families.index', ['project' => $family->project_id, 'archived' => 1]),
        ));
    }

    private function tell(User $user, NotificationType $type, string $key, Family $family, ?User $by = null, array $params = [], ?string $url = null): void
    {
        $this->notifications->send(
            NotificationTarget::PRIVATE,
            $type,
            title: "notifications.{$key}_title",
            message: "notifications.{$key}_message",
            params: ['family' => $family->name, ...$params],
            url: $url ?? route('families.index', ['project' => $family->project_id, 'family' => $family->id]),
            trigger: $by,
            targetId: $user->id,
        );
    }

    private function refuseIf(bool $refused, string $reason): void
    {
        if ($refused) {
            throw ValidationException::withMessages(['family' => __("validation.{$reason}")]);
        }
    }
}
