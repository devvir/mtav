<?php

namespace App\Models;

use App\Models\Concerns\BroadcastsProjectChanges;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A conversation in a project's forum: its title, and its posts, the first opening it.
 * Admins pin a topic (to the top) or close it (no more replies).
 */
class Topic extends Model
{
    use Archivable;
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = ['title', 'is_pinned', 'is_closed'];

    protected $casts = [
        'is_pinned'    => 'boolean',
        'is_closed'    => 'boolean',
        'last_post_at' => 'datetime',
    ];

    /** Nobody answered and it only just went up: it simply goes */
    public function worthKeeping(): bool
    {
        return $this->replies_count > 0 || ! $this->isFresh();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->orderBy('id');
    }

    public function opening(): HasOne
    {
        return $this->hasOne(Post::class)->oldestOfMany();
    }

    /** Everyone who wrote in it */
    public function participants()
    {
        return User::whereIn('id', $this->posts()->select('author_id'));
    }
}
