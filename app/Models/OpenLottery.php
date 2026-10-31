<?php

namespace App\Models;

use App\Enums\LotteryRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A lottery anyone created, with no accounts: known by its link, run by whoever keeps its
 * organizer's secret, once.
 */
class OpenLottery extends Model
{
    protected $fillable = ['slug', 'name', 'items_name', 'organizer_key'];

    protected $hidden = ['organizer_key'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OpenLotteryItem::class)->orderBy('id');
    }

    /** Those taking part: whoever was removed is kept apart, to be told so */
    public function participants(): HasMany
    {
        return $this->hasMany(OpenLotteryParticipant::class)->whereNull('removed_at')->orderBy('id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(LotteryRun::class);
    }

    /** The run that counts: a failed one decided nothing */
    public function run(): HasOne
    {
        return $this->hasOne(LotteryRun::class)->where('status', '!=', LotteryRunStatus::FAILED)->latestOfMany();
    }

    public function isOpen(): bool
    {
        return ! $this->run()->exists();
    }

    public function isOrganizer(?string $key): bool
    {
        return $key && hash_equals($this->organizer_key, self::hashKey($key));
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }
}
