<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\BroadcastsProjectChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        static::created(function (Membership $membership) {
            $membership->communityGroup()?->participants()->syncWithoutDetaching([$membership->user_id => ['pinned_at' => now()]]);
            $membership->startWithAssistant();
        });
        static::deleted(function (Membership $membership) {
            $membership->communityGroup()?->participants()->detach($membership->user_id);
            $membership->assistantConversation()->delete();
        });
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
