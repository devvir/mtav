<?php

namespace App\Models;

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
}
