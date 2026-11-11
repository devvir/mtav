<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What someone (or the Assistant, with its buttons in `extras`) wrote in a conversation of a project's chat */
class Message extends Model
{
    protected $fillable = ['body'];

    protected $casts = [
        'from_assistant' => 'boolean',
        'extras'         => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Until when whoever wrote it may still edit or delete it */
    public function changeableUntil(): CarbonInterface
    {
        return $this->created_at->copy()->addMinutes(config('limits.chat_changes_minutes'));
    }

    public function stillChangeable(): bool
    {
        return ! now()->isAfter($this->changeableUntil());
    }
}
