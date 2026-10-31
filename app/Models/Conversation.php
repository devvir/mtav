<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A chat between people of a project: two of them (one per pair), or a group with its
 * name. Only those in it read it; nobody else does, the project's admins included. Each
 * project has one Community group, with everyone in it and nobody able to leave.
 */
class Conversation extends Model
{
    protected $fillable = ['title'];

    protected $casts = [
        'is_group'        => 'boolean',
        'is_community'    => 'boolean',
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

    public function includes(User $user): bool
    {
        return $this->participants()->whereKey($user->id)->exists();
    }

    /** The conversations someone is in, in a project */
    public function scopeOf(Builder $query, User $user, Project|int $project): void
    {
        $query->where('project_id', $project instanceof Project ? $project->id : $project)
            ->whereHas('participants', fn (Builder $query) => $query->whereKey($user->id));
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
