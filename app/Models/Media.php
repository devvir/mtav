<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\BroadcastsProjectChanges;
use App\Enums\MediaCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file shared in a project. Stored on the private media disk: only people who
 * take part in the project can download it.
 */
class Media extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    public const DISK = 'media';

    protected $fillable = [
        'project_id',
        'owner_id',
        'category',
        'path',
        'thumbnail',
        'description',
        'alt_text',
        'mime_type',
        'file_size',
        'width',
        'height',
    ];

    protected $casts = [
        'category'  => MediaCategory::class,
        'file_size' => 'integer',
        'width'     => 'integer',
        'height'    => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVisual(): bool
    {
        return in_array($this->category, [MediaCategory::IMAGE, MediaCategory::VIDEO]);
    }

    /**
     * Filter by category; VISUAL means images and videos.
     */
    public function scopeCategory(Builder $query, MediaCategory $category): void
    {
        $category === MediaCategory::MEDIA
            ? $query->whereIn('category', [MediaCategory::IMAGE, MediaCategory::VIDEO, MediaCategory::AUDIO])
            : $query->where('category', $category);
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('description', "%{$q}%")
            ->orWhereHas('owner', fn (Builder $query) => $query->search($q)));
    }
}
