<?php

namespace App\Models;

use App\Models\Concerns\HasPolicy;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use ConvertsToJsonResource;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasPolicy;
    use Notifiable;

    protected $guarded = [];

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
        'is_admin' => 'boolean',
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    public function asMember(): ?Member
    {
        return $this->is_admin ? null : Member::make($this->getAttributes());
    }

    public function asAdmin(): ?Admin
    {
        return $this->is_admin ? Admin::make($this->getAttributes()) : null;
    }

    /**
     * All projects that the user belongs to.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_id')
            ->wherePivot('active', true)
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): void
    {
        $query->wherePivot('active', true);
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

    public function isMember(): bool
    {
        return ! $this->is_admin;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isNotAdmin(): bool
    {
        return ! $this->isAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && in_array($this->getKey(), config('auth.superadmins'));
    }

    public function isNotSuperAdmin(): bool
    {
        return ! $this->isSuperAdmin();
    }

    public function isVerified(): bool
    {
        return (bool) $this->email_verified_at;
    }
}
