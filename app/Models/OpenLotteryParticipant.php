<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Someone in an open lottery: known by a name they chose and a secret their browser keeps.
 */
class OpenLotteryParticipant extends Model
{
    protected $fillable = ['name', 'key', 'ranked_at', 'removed_at', 'item_id'];

    protected $hidden = ['key'];

    protected $casts = ['ranked_at' => 'datetime', 'removed_at' => 'datetime'];

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(OpenLottery::class, 'open_lottery_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OpenLotteryItem::class);
    }

    /** What they ranked, most wanted first */
    public function ranking(): BelongsToMany
    {
        return $this->belongsToMany(OpenLotteryItem::class, 'open_lottery_rankings', 'participant_id', 'item_id')
            ->orderByPivot('rank');
    }
}
