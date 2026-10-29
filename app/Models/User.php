<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

/**
 * A person with an account. What they may do in a project depends on their
 * Membership there; superadmins manage the whole platform.
 */
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
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
        'email_public',
        'phone_public',
        'legal_id',
        'about',
        'locale',
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
        'is_owner'          => 'boolean',
        'email_public'      => 'boolean',
        'phone_public'      => 'boolean',
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

    /**
     * The projects that are theirs to open: those they take part in, or every one for a
     * superadmin, who oversees them all (archived ones are gone either way)
     */
    public function reachableProjects(): Builder
    {
        return $this->isSuperadmin()
            ? Project::query()
            : Project::whereHas('memberships', fn (Builder $query) => $query->where('user_id', $this->id));
    }

    /** The language mail and notifications reach them in (null: the platform's) */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Mail goes to the account's address; a link confirming a new one goes to that one,
     * since receiving it is what proves owning it.
     */
    public function routeNotificationForMail(?Notification $notification = null): string
    {
        return $notification instanceof VerifyEmailNotification ? $notification->address($this) : $this->email;
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

    /**
     * By name, and by email where the person shares it; by every email for whoever sees
     * them all (admins of the project, superadmins). A search never tells who has an
     * address they keep private.
     */
    public function scopeSearch(Builder $query, string $q, bool $byEmail = false): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereRaw("CONCAT_WS(' ', firstname, lastname) LIKE ?", ["%{$q}%"])
            ->orWhere(fn (Builder $query) => $query
                ->where('email', 'like', "%{$q}%")
                ->unless($byEmail, fn (Builder $query) => $query->where('email_public', true))));
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
