<?php

namespace App\Models;

use App\Models\Concerns\BroadcastsProjectChanges;
use App\Models\Concerns\Archivable;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What someone wrote in a forum topic */
class Post extends Model
{
    use Archivable;
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = ['body'];

    protected $casts = ['edited_at' => 'datetime'];

    /** Taken back right after writing it, it simply goes */
    public function worthKeeping(): bool
    {
        return ! $this->isFresh();
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
