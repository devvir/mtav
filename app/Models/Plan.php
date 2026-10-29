<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The project plan: a canvas with the project boundary and the shapes on it.
 */
class Plan extends Model
{
    protected $fillable = [
        'project_id',
        'polygon',
        'width',
        'height',
        'unit_system',
    ];

    protected $casts = [
        'polygon' => 'array',
        'width'   => 'float',
        'height'  => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Its shapes, in their stack's order (the first drawn underneath) */
    public function items(): HasMany
    {
        return $this->hasMany(PlanItem::class)->orderBy('position')->orderBy('id');
    }
}
