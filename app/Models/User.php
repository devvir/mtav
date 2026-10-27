<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A person with an account. What they may do in a project depends on their
 * Membership there; superadmins manage the whole platform.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'email',
        'new_email',
        'password',
        'firstname',
        'lastname',
        'phone',
        'legal_id',
        'about',
        'avatar',
        'email_verified_at',
    ];

    protected $attributes = [
        'is_superadmin' => false,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_superadmin'     => 'boolean',
        'password'          => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'memberships')
            ->withPivot('family_id', 'is_admin')
            ->withTimestamps();
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'owner_id');
    }

    /**
     * Events this user answered (attending or not).
     */
    public function rsvps(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_rsvps')
            ->withPivot('attending')
            ->withTimestamps();
    }

    public function isSuperadmin(): bool
    {
        return $this->is_superadmin;
    }

    /**
     * This user's membership in a project (memberships are loaded once per user).
     */
    public function membershipIn(Project|int $project): ?Membership
    {
        $projectId = $project instanceof Project ? $project->id : $project;

        return $this->memberships->firstWhere('project_id', $projectId);
    }

    /**
     * Whether the user takes part in the project (or oversees it as a superadmin).
     */
    public function canAccess(Project|int $project): bool
    {
        return $this->isSuperadmin() || (bool) $this->membershipIn($project);
    }

    /**
     * Whether the user may configure the project.
     */
    public function manages(Project|int $project): bool
    {
        return $this->isSuperadmin() || (bool) $this->membershipIn($project)?->is_admin;
    }

    /**
     * The family the user belongs to in a project, if any.
     */
    public function familyIn(Project|int $project): ?Family
    {
        return $this->membershipIn($project)?->family;
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('firstname')->orderBy('lastname');
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereRaw("CONCAT_WS(' ', firstname, lastname) LIKE ?", ["%{$q}%"])
            ->orWhere('email', 'like', "%{$q}%"));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->firstname} {$this->lastname}"));
    }

    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => strtolower(trim($value)));
    }
}
