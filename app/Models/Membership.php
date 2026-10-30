<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\BroadcastsProjectChanges;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's part in a project: as an admin, as a member of a family, or both.
 */
class Membership extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    /** Pages call the people of a project "people" */
    protected string $broadcastAs = 'people';

    protected $fillable = [
        'user_id',
        'project_id',
        'family_id',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
