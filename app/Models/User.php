<?php

namespace App\Models;

use App\Models\Concerns\HasPolicy;
use Devvir\ResourceTools\Concerns\ConvertsToJsonResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use ConvertsToJsonResource;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasPolicy;
    use Notifiable;
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
        'is_admin' => 'boolean',
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
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
     * Ensure email is always stored in lowercase.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
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

    public function scopeInProject(Builder $query, int|Project $project): void
    {
        $projectId = is_int($project) ? $project : $project->getKey();

        $query->whereHas(
            'projects',
            fn ($q) => $q->where('projects.id', $projectId)
        );
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

    public function isVerified(): bool
    {
        return (bool) $this->email_verified_at;
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
