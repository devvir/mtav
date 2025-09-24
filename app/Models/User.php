<?php

namespace App\Models;

use App\Models\Concerns\HasPolicy;
use BadMethodCallException;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasPolicy;
    use Notifiable;
    use ConvertsToJsonResource;

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin'          => 'boolean',
        'password'          => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    /**
     * A user may belong to exactly one family or none.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * All projects that the user is a member of.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->wherePivot('active', true)
            ->withTimestamps();
    }

    public function manages(Project $project): bool
    {
        return $this->isSuperAdmin()
            || ($this->isAdmin() && $this->projects->contains($project));
    }

    /**
     * Get the project that the user is currently an active member of (one or none).
     */
    public function getProjectAttribute(): ?Project
    {
        if ($this->isAdmin()) {
            throw new BadMethodCallException('The Project attribute is only available to Members (not Admins).');
        }

        return $this->projects->where('pivot.active', true)->first();
    }

    public function scopeMembers(Builder $query): void
    {
        $query->whereNotNull('family_id')->where('is_admin', false);
    }

    public function scopeAdmins(Builder $query): void
    {
        $query->where('is_admin', true);
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('firstname')->orderBy('lastname');
    }

    public function scopeSearch(Builder $query, string $q, bool $searchFamily = false): void
    {
        $query
            ->whereLike('email', "%$q%")
            ->orWhereLike('phone', "%$q%")
            ->orWhereRaw('CONCAT(firstname, " ", lastname) LIKE ?', "%$q%")
            ->when($searchFamily, fn (Builder $query) => $query->orWhereHas(
                'family',
                fn (Builder $query) => $query->whereLike('name', "%$q%")
            ));
    }

    public function joinProject(Project|int $project): self
    {
        model($project, Project::class)->addUser($this);

        return $this;
    }

    public function leaveProject(Project $project): self
    {
        $project->removeUser($this);

        return $this;
    }

    public function switchProject(Project $project): self
    {
        if ($this->isAdmin()) {
            throw new BadMethodCallException('Only regular Members (not Admins) may switch Projects.');
        }

        $this->project?->removeUser($this);
        $project->addUser($this);

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->is_admin;
    }

    public function isNotAdmin(): bool
    {
        return ! $this->isAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->getKey(), config('auth.superadmins'));
    }

    public function isNotSuperAdmin(): bool
    {
        return ! $this->isSuperAdmin();
    }
}
