<?php

namespace App\Models;

use App\Enums\MediaCategory;
use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use ProjectScope;
    use SoftDeletes;

    protected $casts = [
        'category'  => MediaCategory::class,
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

    public function isAudio(): bool
    {
        return $this->category === MediaCategory::AUDIO;
    }

    public function isDocument(): bool
    {
        return $this->category === MediaCategory::DOCUMENT;
    }

    public function isImage(): bool
    {
        return $this->category === MediaCategory::IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->category === MediaCategory::VIDEO;
    }

    public function isVisual(): bool
    {
        return in_array($this->category, [MediaCategory::IMAGE, MediaCategory::VIDEO]);
    }

    public function scopeAudios(Builder $query): void
    {
        $query->where('category', MediaCategory::AUDIO);
    }

    public function scopeDocuments(Builder $query): void
    {
        $query->where('category', MediaCategory::DOCUMENT);
    }

    public function scopeImages(Builder $query): void
    {
        $query->where('category', MediaCategory::IMAGE);
    }

    public function scopeVideos(Builder $query): void
    {
        $query->where('category', MediaCategory::VIDEO);
    }

    public function scopeVisual(Builder $query): void
    {
        $query->whereIn('category', [MediaCategory::IMAGE, MediaCategory::VIDEO]);
    }

    public function scopeCategory(Builder $query, MediaCategory $category): void
    {
        match ($category) {
            MediaCategory::AUDIO    => $query->audios(),
            MediaCategory::DOCUMENT => $query->documents(),
            MediaCategory::IMAGE    => $query->images(),
            MediaCategory::VIDEO    => $query->videos(),
            MediaCategory::VISUAL   => $query->visual(),
        };
    }

    public function scopeSearch(Builder $query, string $q, bool $searchOwner = false): void
    {
        $query
            ->whereLike('description', "%{$q}%")
            ->when($searchOwner, fn (Builder $query) => $query->orWhereHas(
                'owner',
                fn (Builder $query) => $query->whereRaw('CONCAT(firstname, " ", lastname) LIKE ?', "%{$q}%")
            ));
    }
}
