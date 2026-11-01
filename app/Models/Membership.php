<?php

namespace App\Models;

use App\Services\FamilyService;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\BroadcastsProjectChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A user's part in a project: as an admin, as a member of a family, or both.
 */
class Membership extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    /** Pages call the people of a project "people" */
    protected string $broadcastAs = 'people';

    protected $fillable = [
        'user_id',
        'project_id',
        'family_id',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    /** Everyone taking part is in the project's Community group, pinned to begin with, and only they */
    protected static function booted(): void
    {
        // A family is shown while someone is in it, however they got there
        static::saving(function (Membership $membership) {
            if ($membership->family_id && $membership->isDirty('family_id')) {
                Family::onlyTrashed()->find($membership->family_id)?->restore();
            }
        });
        // Who was ever in a family: it may take them back, and nobody else
        static::saved(function (Membership $membership) {
            if ($membership->family_id && ($membership->wasRecentlyCreated || $membership->wasChanged('family_id'))) {
                DB::table('family_people')->insertOrIgnore([
                    'family_id'  => $membership->family_id,
                    'user_id'    => $membership->user_id,
                    'created_at' => now(),
                ]);
            }
        });
        static::created(function (Membership $membership) {
            // However they got in, what they asked the project is settled by it
            JoinRequest::where('user_id', $membership->user_id)->where('project_id', $membership->project_id)->delete();
            $membership->communityGroup()?->participants()->syncWithoutDetaching([$membership->user_id => ['pinned_at' => now()]]);
            $membership->startWithAssistant();
        });
        static::updated(function (Membership $membership) {
            if ($membership->wasChanged('family_id')) {
                $membership->family_id && $membership->closeRequests();
                app(FamilyService::class)->discardIfEmpty(Family::find($membership->getOriginal('family_id')));
            }
        });
        static::deleted(function (Membership $membership) {
            $membership->communityGroup()?->participants()->detach($membership->user_id);
            $membership->assistantConversation()->delete();
            $membership->closeRequests();
            app(FamilyService::class)->discardIfEmpty(Family::find($membership->family_id));
        });
    }

    /** What they were asking no longer stands (a family's decline does) */
    private function closeRequests(): void
    {
        FamilyJoinRequest::pending()->where('user_id', $this->user_id)->where('project_id', $this->project_id)->delete();
    }

    private function communityGroup(): ?Conversation
    {
        return Conversation::where('project_id', $this->project_id)->where('is_community', true)->first();
    }

    /** Their conversation with the Assistant: theirs alone, pinned to begin with */
    private function startWithAssistant(): void
    {
        $conversation = (new Conversation())->forceFill([
            'project_id'   => $this->project_id,
            'is_assistant' => true,
            'pair'         => "assistant:{$this->user_id}",
        ]);
        $conversation->save();
        $conversation->participants()->attach($this->user_id, ['pinned_at' => now()]);
    }

    private function assistantConversation(): Builder
    {
        return Conversation::where('project_id', $this->project_id)->where('pair', "assistant:{$this->user_id}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
