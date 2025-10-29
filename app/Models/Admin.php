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

    /**
     * Check if admin manages a project.
     */
    public function manages(Project|int $project): bool
    {
        return $this->isSuperAdmin()
            || $this->projects->contains($project);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('admin', function ($query) {
            $query->where('is_admin', true);
        });
    }
}
