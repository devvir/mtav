<?php

namespace App\Models;

use App\Models\Concerns\DerivedRelations;
use App\Models\Concerns\ExtendedRelations;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasNotifications;
use App\Models\Concerns\HasPolicy;
use App\Models\Concerns\ProjectScope;
use App\Notifications\ResetPasswordNotification;
use App\Relations\BelongsToOneOrMany;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use ConvertsToJsonResource;
    use DerivedRelations;
    use ExtendedRelations;
    use HasFactory;
    use HasMedia;
    use HasNotifications {
        HasNotifications::notifications insteadof Notifiable;
        HasNotifications::readNotifications insteadof Notifiable;
        HasNotifications::unreadNotifications insteadof Notifiable;
    }
    use HasPolicy;
    use Notifiable;
    use ProjectScope;
    use SoftDeletes;

    /**
     * The table associated with the model and its children (Member/Admin).
     *
     * @var string|null
     */
    protected $table = 'users';

    /** @var array<string> */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_admin'               => 'boolean',
        'password'               => 'hashed',
        'email_verified_at'      => 'datetime',
        'invitation_accepted_at' => 'datetime',
    ];

    /**
     * Sub-instance as Member/Admin (depends on @is_admin)
     */
    protected ?Admin $adminCast = null;

    protected ?Member $memberCast = null;

    public function asMember(): ?Member
    {
        return $this->memberCast;
    }

    public function asAdmin(): ?Admin
    {
        return $this->adminCast;
    }

    /**
     * All projects this User belongs to.
     *
     * Uses BelongsToOneOrMany to allow Members to call @one() on it.
     */
    public function projects(): BelongsToOneOrMany
    {
        return $this
            ->belongsToOneOrMany(Project::class, 'project_user', 'user_id')
            ->wherePivot('active', true)
            ->withTimestamps();
    }

    /**
     * All media uploaded by this user.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'owner_id');
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('firstname')->orderBy('lastname');
    }

    public function isMember(): bool
    {
        return ! $this->is_admin;
    }

    public function isNotMember(): bool
    {
        return ! $this->isMember();
    }

    public function isAdmin(): bool
    {
        return $this->is_admin || $this->isSuperadmin();
    }

    public function isNotAdmin(): bool
    {
        return ! $this->isAdmin();
    }

    public function isSuperadmin(): bool
    {
        return $this->is_admin && in_array($this->email, config('auth.superadmins'));
    }

    public function isNotSuperadmin(): bool
    {
        return ! $this->isSuperadmin();
    }

    public function isInvited(): bool
    {
        return is_null($this->invitation_accepted_at);
    }

    public function completedRegistration(): bool
    {
        return ! $this->isInvited();
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Ensure email is always stored in lowercase.
     */
    protected function fullname(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->firstname . ' ' . $this->lastname),
        );
    }

    /**
     * Ensure email is always stored in lowercase.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    protected static function booted(): void
    {
        $doCast = fn (User $user) => $user->isAdmin()
            ? ($user->adminCast = Admin::make($user->getAttributes()))
            : ($user->memberCast = Member::make($user->getAttributes()));

        if (static::class === User::class) {
            static::created($doCast);
            static::retrieved($doCast);
            static::saved($doCast);
        }
    }
}
