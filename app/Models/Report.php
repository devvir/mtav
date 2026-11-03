<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Something someone told the platform's team through the Assistant: a problem, or an idea */
class Report extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** How many more this person may send today */
    public static function leftToday(User $user): int
    {
        return max(0, (int) config('assistant.reports_per_day') - self::where('user_id', $user->id)->where('created_at', '>=', now()->startOfDay())->count());
    }
}
