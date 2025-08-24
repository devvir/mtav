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

/**
 * @property int $id
 * @property int|null $family_id
 * @property string $email
 * @property string|null $phone
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Family|null $family
 * @property-read \App\Models\Project|null $project
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFamilyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasPolicy;
    use Notifiable;
    use ConvertsToJsonResource;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'firstname',
        'lastname',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $cats = [
        'is_admin'          => 'boolean',
        'password'          => 'hashed',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_admin'          => 'boolean',
            'password'          => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * A user may belong to exactly one family or none.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * All projects that the user is a member of (active or not).
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('active')
            ->withTimestamps();
    }

    public function managedProjects(): BelongsToMany
    {
        if ($this->isNotAdmin()) {
            throw new BadMethodCallException('Only admins can manage projects.');
        }

        return $this->projects();
    }

    public function manages(Project $project): bool
    {
        if ($this->isNotAdmin()) {
            throw new BadMethodCallException('Only admins can manage projects.');
        }

        return $this->isSuperAdmin()
            || $this->managedProjects->contains($project);
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

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('firstname')->orderBy('lastname');
    }

    public function scopeSearch(Builder $query, string $q, bool $searchFamily = false): void
    {
        $query
            ->whereLike('email', "%$q%")
            ->orWhereLike('phone', "%$q%")
            ->orWhereLike('firstname', "%$q%")
            ->orWhereLike('lastname', "%$q%")
            ->when($searchFamily, fn (Builder $query) => $query
                ->orWhereHas('family', fn (Builder $query) => $query->whereLike('name', "%$q%")
            ));
    }

    public function joinProject(Project $project): self
    {
        $project->addUser($this);

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
