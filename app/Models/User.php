<?php

namespace App\Models;

use App\Models\Concerns\DerivedRelations;
use App\Models\Concerns\HasPolicy;
use App\Models\Concerns\ProjectScope;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use ConvertsToJsonResource;
    use DerivedRelations;
    use HasFactory;
    use HasPolicy;
    use Notifiable;
    use ProjectScope;
    use SoftDeletes;

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
     * All projects that the user belongs to.
     */
    public function projects(): BelongsToMany
    {
        return $this
            ->belongsToMany(Project::class, 'project_user', 'user_id')
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

    /**
     * All images uploaded by this user.
     */
    public function images(): HasMany
    {
        return $this->media()->images();
    }

    /**
     * All videos uploaded by this user.
     */
    public function videos(): HasMany
    {
        return $this->media()->videos();
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('firstname')->orderBy('lastname');
    }

    public function scopeSearch(Builder $query, string $q, bool $searchFamily = false): void
    {
        $query->where(
            fn (Builder $query) => $query
                ->whereRaw('CONCAT(firstname, " ", lastname) LIKE ?', "%$q%")
                ->when($searchFamily, fn (Builder $query) => $query->orWhereHas(
                    'family',
                    fn (Builder $query) => $query->whereLike('name', "%$q%")
                ))
        );
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
     * Ensure email is always stored in lowercase.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    protected static function booted()
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
