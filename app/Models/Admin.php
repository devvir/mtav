<?php

namespace App\Models;

class Admin extends User
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'users';

    public function manages(Project $project): bool
    {
        return $this->isSuperAdmin()
            || ($this->isAdmin() && $this->projects->contains($project));
    }

    protected static function booted(): void
    {
        static::addGlobalScope('admin', function ($query) {
            $query->where('is_admin', true);
        });
    }
}
