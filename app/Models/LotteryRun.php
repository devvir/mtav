<?php

namespace App\Models;

use App\Enums\LotteryRunStatus;
use App\Models\Concerns\BroadcastsProjectChanges;
use App\Models\Concerns\RecordsActivity;
use App\Services\Lottery\Contracts\LotteryHost;
use App\Services\LotteryService;
use App\Services\OpenLotteryService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One execution of a project's lottery. A completed run is final unless an admin
 * invalidates it, giving a reason that every member of the project can read.
 */
class LotteryRun extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = [
        'uuid',
        'project_id',
        'open_lottery_id',
        'status',
        'solver',
        'options',
        'started_by',
        'started_at',
        'reveal_at',
        'result',
        'finished_at',
        'failure',
        'invalidated_by',
        'invalidated_at',
        'invalidation_reason',
    ];

    protected $casts = [
        'status'         => LotteryRunStatus::class,
        'options'        => 'array',
        'result'         => 'array',
        'reveal_at'      => 'datetime',
        'started_at'     => 'datetime',
        'finished_at'    => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    /** How a run's failure is kept: words to say, not yet said in anyone's language */
    public static function failureOf(string $messageKey, array $messageParams = []): string
    {
        return json_encode(['key' => $messageKey, 'params' => $messageParams], JSON_UNESCAPED_UNICODE);
    }

    /** Why it failed, in the language of whoever reads it */
    public function failureMessage(): ?string
    {
        $failure = json_decode((string) $this->failure, true);

        return is_array($failure) ? __($failure['key'], $failure['params']) : $this->failure;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function openLottery(): BelongsTo
    {
        return $this->belongsTo(OpenLottery::class);
    }

    /** What this run belongs to: it says what the run decides from, and takes the result */
    public function host(): LotteryHost
    {
        return app($this->open_lottery_id ? OpenLotteryService::class : LotteryService::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function invalidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invalidated_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(LotteryAudit::class)->orderBy('id');
    }
}
