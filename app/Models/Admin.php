<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends User
{
    /**
     * Get events created by this admin.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'creator_id');
    }

    /**
     * Get upcoming events created by this admin.
     */
    public function upcomingEvents(): HasMany
    {
        return $this->events()->upcoming();
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->whereRaw('CONCAT(firstname, " ", lastname) LIKE ?', "%{$q}%");
    }

    /**
     * Check if admin manages a project.
     */
    public function manages(Project|int $project): bool
    {
        return $this->isSuperadmin()
            || $this->projects->contains($project);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('admin', function ($query) {
            $query->where('is_admin', true);
        });
    }
}
