<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\BroadcastsProjectChanges;
use App\Models\Concerns\RecordsActivity;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The lottery participant: it ranks the units of its type and receives one of them. It exists
 * while it holds someone (FamilyService): archived otherwise, for those who were in it to come
 * back to, or an admin to add someone to. Its name need not be the only one of its kind.
 */
class Family extends Model
{
    use Archivable;
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = [
        'project_id',
        'unit_type_id',
        'name',
        'avatar',
    ];

    public function project(): BelongsTo
    {
        // Its project may be archived: its people still leave, and their accounts still go
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function worthKeeping(): bool
    {
        return true;
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(FamilyJoinRequest::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot('project_id', 'is_admin')
            ->withTimestamps();
    }

    /** Everyone who was ever in it, as long as they have an account */
    public function everyone(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'family_people')->withPivot('created_at')->orderByPivot('created_at');
    }

    /**
     * The unit the lottery assigned to this family.
     */
    public function unit(): HasOne
    {
        return $this->hasOne(Unit::class);
    }

    /**
     * Explicit rankings only. The complete ranking (every unit of the family's type)
     * comes from App\Services\Lottery\PreferencesService.
     */
    public function preferences(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_preferences')
            ->withPivot('rank')
            ->orderByPivot('rank')
            ->withTimestamps();
    }

    /**
     * For a list that names families: where a name repeats, each says who is in it (or which it
     * is, when nobody is).
     *
     * @param iterable<array{id: int, name: string}> $rows
     *
     * @return list<array{id: int, name: string}>
     */
    public static function tellingApart(iterable $rows): array
    {
        $rows = collect($rows);
        $repeated = $rows->countBy(fn (array $row) => mb_strtolower($row['name']))->filter(fn (int $times) => $times > 1);
        $tied = $rows->filter(fn (array $row) => $repeated->has(mb_strtolower($row['name'])));

        if ($tied->isEmpty()) {
            return $rows->values()->all();
        }

        $people = self::withTrashed()->whereKey($tied->pluck('id'))->with('members:id,firstname')->get()->keyBy('id');

        return $rows->map(function (array $row) use ($repeated, $people) {
            if ($repeated->has(mb_strtolower($row['name']))) {
                $who = $people->get($row['id'])?->members->pluck('firstname')->join(', ') ?: "#{$row['id']}";
                $row['name'] .= " ({$who})";
            }

            return $row;
        })->values()->all();
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }

    /** By its name or its members' (their emails too, for whoever may see them) */
    public function scopeSearch(Builder $query, string $q, bool $byEmail = false, bool $nameOnly = false): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('name', Like::containing($q))
            ->orWhereHas('members', fn (Builder $query) => $query->search($q, $byEmail, $nameOnly)));
    }
}
