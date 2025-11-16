<?php

namespace App\Models;

use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use ProjectScope;
    use SoftDeletes;

    protected $casts = [
        'width'     => 'integer',
        'height'    => 'integer',
        'file_size' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public URL for this media file.
     */
    public function url(): string
    {
        return Storage::url($this->path);
    }

    /**
     * Check if this media is an image.
     */
    public function isImage(): bool
    {
        return $this->category === 'image';
    }

    /**
     * Check if this media is a video.
     */
    public function isVideo(): bool
    {
        return $this->category === 'video';
    }

    public function scopeImages(Builder $query): void
    {
        $query->where('category', 'image');
    }

    public function scopeVideos(Builder $query): void
    {
        $query->where('category', 'video');
    }

    public function scopeSearch(Builder $query, string $q, bool $searchOwner = false): void
    {
        $query
            ->whereLike('description', "%$q%")
            ->when($searchOwner, fn (Builder $query) => $query->orWhereHas(
                'owner',
                fn (Builder $query) => $query->whereRaw('CONCAT(firstname, " ", lastname) LIKE ?', "%$q%")
            ));
    }
}
