<?php

namespace App\Models;

use App\Observers\ProjectObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[ObservedBy([ProjectObserver::class])]
class Project extends Model
{
    public static function current(): ?Project
    {
        return state('project');
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'project_user', relatedPivotKey: 'user_id')
            ->withPivot('active')
            ->withTimestamps();
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'project_user', relatedPivotKey: 'user_id')
            ->withPivot('active')
            ->withTimestamps();
    }

    public function unitTypes(): HasMany
    {
        return $this->hasMany(UnitType::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    public function addMember(Member|int $memberOrId): self
    {
        $this->members()->syncWithPivotValues(
            model($memberOrId, Member::class),
            ['active' => true],
            detaching: false
        );

        return $this;
    }

    public function removeMember(Member|int $memberOrId): self
    {
        $this->members()->updateExistingPivot(
            model($memberOrId, Member::class),
            ['active' => false]
        );

        return $this;
    }

    public function addAdmin(Admin|int $adminOrId): self
    {
        $this->admins()->syncWithPivotValues(
            model($adminOrId, Admin::class),
            ['active' => true],
            detaching: false
        );

        return $this;
    }

    public function hasMember(Member|int $memberOrId): bool
    {
        $memberId = $memberOrId instanceof Member ? $memberOrId->id : $memberOrId;

        return $this->members()->where('users.id', $memberId)->exists();
    }

    public function hasAdmin(Admin|int $adminOrId): bool
    {
        $adminId = $adminOrId instanceof Admin ? $adminOrId->id : $adminOrId;

        return $this->admins()->where('users.id', $adminId)->exists();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }

    public static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('available', function (Builder $builder) {
            if (Auth::guest() || Auth::user()->isSuperadmin()) {
                return;
            }

            $validIds = DB::table('project_user')
                ->where('user_id', Auth::id())
                ->pluck('project_id');

            $builder->whereIn('projects.id', $validIds);
        });
    }
}
