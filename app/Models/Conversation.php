<?php

namespace App\Models;

use App\Enums\ProjectFeature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A chat between people of a project: two of them (one per pair), or a group with its
 * name. Only those in it read it; nobody else does, the project's admins included. Each
 * project has one Community group, with everyone in it and nobody able to leave, and each
 * person has one conversation with the Assistant (App\Services\Assistant). Outside any project
 * (in none yet, or none chosen), someone has one more, of no project: with the Assistant alone.
 */
class Conversation extends Model
{
    protected $fillable = ['title'];

    protected $casts = [
        'is_group'        => 'boolean',
        'is_community'    => 'boolean',
        'is_assistant'    => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /** The key of a one-on-one conversation: its two people, in order */
    public static function pairOf(int $one, int $other): string
    {
        return min($one, $other) . ':' . max($one, $other);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('read_up_to', 'pinned_at')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    /**
     * Those in it who may read it now, the only ones told of its messages: still of its project
     * (or a support account), never a blocked account. Whoever left stays in it, should they
     * come back, but hears nothing of it meanwhile.
     */
    public function audience(): BelongsToMany
    {
        return $this->participants()->whereNull('users.blocked_at')
            ->when($this->project_id, fn (Builder $people) => $people->where(fn (Builder $still) => $still
                ->where('users.is_superadmin', true)
                ->orWhereHas('memberships', fn (Builder $membership) => $membership->where('project_id', $this->project_id))));
    }

    public function includes(User $user): bool
    {
        return $this->participants()->whereKey($user->id)->exists();
    }

    /** The conversations someone is in, in a project; with none, their own with the Assistant */
    public function scopeOf(Builder $query, User $user, Project|int|null $project): void
    {
        $project = $project === null || $project instanceof Project ? $project : Project::find($project);

        $query->where('project_id', $project?->id)
            ->whereHas('participants', fn (Builder $query) => $query->whereKey($user->id))
            ->when($project && ! $project->hasFeature(ProjectFeature::ASSISTANT), fn (Builder $query) => $query->where('is_assistant', false))
            ->when(! $project, fn (Builder $query) => $query->where('is_assistant', true));
    }

    /**
     * Those with something new for them: a last message after what they read, not theirs
     */
    public function scopeUnreadBy(Builder $query, User $user): void
    {
        $query->whereNotNull('last_message_id')
            ->whereExists(fn ($sub) => $sub->from('conversation_user')
                ->whereColumn('conversation_user.conversation_id', 'conversations.id')
                ->where('conversation_user.user_id', $user->id)
                ->where(fn ($read) => $read->whereNull('read_up_to')->orWhereColumn('read_up_to', '<', 'conversations.last_message_id')))
            ->whereExists(fn ($sub) => $sub->from('messages')
                ->whereColumn('messages.id', 'conversations.last_message_id')
                ->where(fn ($by) => $by->whereNull('messages.user_id')->orWhere('messages.user_id', '!=', $user->id)));
    }
}
