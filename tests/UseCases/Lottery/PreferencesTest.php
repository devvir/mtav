<?php

/**
 * Each family ranks the units of its own type, from most to least wanted. Any member
 * of the family can change the ranking until the lottery starts. Families see only
 * their own ranking.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->type = aUnitType($this->project);
    $this->units = collect(range(1, 3))->map(fn () => aUnit($this->type));

    $this->family = aFamily($this->project, $this->type);
    $this->member = aMemberOf($this->family);
});

function rankingSeenBy($test, $member): array
{
    return collect($test->as($member, $test->project)->get(route('lottery.show'))->inertiaProps('ranking'))
        ->pluck('id')->all();
}

function rank($test, $member, iterable $units)
{
    return $test->as($member, $test->project)->put(route('lottery.preferences'), [
        'preferences' => collect($units)->pluck('id')->values()->all(),
    ]);
}

it('shows a family a ranking of exactly the units of its type', function () {
    aUnit(aUnitType($this->project)); // A unit of another type

    expect(rankingSeenBy($this, $this->member))->toEqualCanonicalizing($this->units->pluck('id')->all());
});

it('saves a family\'s ranking, shared by all its members', function () {
    $ranking = $this->units->reverse();
    $relative = aMemberOf($this->family);

    rank($this, $this->member, $ranking)->assertSessionHasNoErrors();

    expect(rankingSeenBy($this, $relative))->toBe($ranking->pluck('id')->values()->all());
});

it('keeps unranked units after the ranked ones', function () {
    [$first, $second] = [$this->units[2], $this->units[0]];

    rank($this, $this->member, [$first, $second])->assertSessionHasNoErrors();

    expect(array_slice(rankingSeenBy($this, $this->member), 0, 2))->toBe([$first->id, $second->id]);
});

it('rejects units of another type', function () {
    $foreignUnit = aUnit(aUnitType($this->project));

    rank($this, $this->member, [...$this->units, $foreignUnit])->assertSessionHasErrors();
});

it('never shows a family the ranking of another family', function () {
    $neighbour = aMemberOf(aFamily($this->project, $this->type));
    [$a, $b, $c] = $this->units->all();

    rank($this, $this->member, [$a, $b, $c]);
    rank($this, $neighbour, [$c, $b, $a]);

    expect(rankingSeenBy($this, $this->member))->toBe([$a->id, $b->id, $c->id])
        ->and(rankingSeenBy($this, $neighbour))->toBe([$c->id, $b->id, $a->id]);
});

it('adds a new unit of the family\'s type to its ranking', function () {
    rankingSeenBy($this, $this->member);
    $newUnit = aUnit($this->type);

    expect(rankingSeenBy($this, $this->member))->toContain($newUnit->id)->toHaveCount(4);
});

it('follows the family when it moves to another unit type', function () {
    $newType = aUnitType($this->project);
    $newUnits = collect(range(1, 2))->map(fn () => aUnit($newType));
    rank($this, $this->member, $this->units);

    $this->family->update(['unit_type_id' => $newType->id]);

    expect(rankingSeenBy($this, $this->member))->toEqualCanonicalizing($newUnits->pluck('id')->all());
});

it('cannot be changed once the lottery has started', function () {
    $before = rankingSeenBy($this, $this->member);
    aLotteryInProgress($this->project);

    rank($this, $this->member, $this->units->reverse())->assertSessionHas('error');

    expect(rankingSeenBy($this, $this->member))->toBe($before);
});
