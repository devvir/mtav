<?php

use App\Models\Project;

/**
 * A limited project is used at a bounded pace, so that nobody runs a scraper or a flood through
 * a project nobody vetted: so many requests a minute per person, fewer of them changing
 * something. An approved project is used at any pace its routes allow.
 */

beforeEach(function () {
    config(['limits.unapproved.requests_per_minute' => 5, 'limits.unapproved.writes_per_minute' => 2]);

    $this->project = anUnapprovedProject();
    $this->admin = $this->project->admins()->sole();
    $this->type = aUnitType($this->project);
    $this->family = aFamily($this->project, $this->type);
    $this->member = aMemberOf($this->family);
});

it('lets so many requests a minute through, then refuses the rest, in a limited project', function () {
    foreach (range(1, 5) as $_) {
        $this->as($this->member, $this->project)->get(route('people.index'))->assertOk();
    }

    $this->as($this->member, $this->project)->get(route('people.index'))->assertStatus(429);
});

it('lets fewer of them change something', function () {
    $units = [aUnit($this->type), aUnit($this->type)];
    $rank = fn () => $this->as($this->member, $this->project)->put(route('lottery.preferences'), ['preferences' => collect($units)->pluck('id')->all()]);

    $rank()->assertSessionHasNoErrors();
    $rank()->assertSessionHasNoErrors();
    $rank()->assertSessionHas('error', __('flash.too_many_attempts'));
});

it('counts each person apart', function () {
    foreach (range(1, 5) as $_) {
        $this->as($this->member, $this->project)->get(route('people.index'))->assertOk();
    }

    $this->as($this->admin, $this->project)->get(route('people.index'))->assertOk();
});

it('paces an approved project only by its routes', function () {
    $this->project->update(['approved_at' => now()]);

    foreach (range(1, 6) as $_) {
        $this->as($this->member, $this->project)->get(route('people.index'))->assertOk();
    }
});

it('keeps the pace per project', function () {
    $other = anUnapprovedProject();
    $other->memberships()->create(['user_id' => $this->member->id, 'family_id' => aFamily($other)->id]);

    foreach (range(1, 5) as $_) {
        $this->as($this->member, $this->project)->get(route('people.index'))->assertOk();
    }

    $this->as($this->member, $other)->get(route('people.index', $other))->assertOk();
});
