<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EventPolicy extends ProjectResourcePolicy
{
    /**
     * Drafts are visible to admins only.
     *
     * @param Event $model
     */
    public function view(User $user, Model $model): bool
    {
        return $model->is_published ? $user->canAccess($model->project_id) : $user->manages($model->project_id);
    }

    /**
     * The lottery event exists as long as its project does.
     *
     * @param Event $model
     */
    public function delete(User $user, Model $model): bool
    {
        return ! $model->isLottery() && parent::delete($user, $model);
    }

    /**
     * Anyone in the project answers whether they attend a published event that asks.
     */
    public function rsvp(User $user, Event $event): bool
    {
        return $event->allows_rsvp
            && $event->is_published
            && ! $event->isLottery()
            && (bool) $user->membershipIn($event->project_id);
    }
}
