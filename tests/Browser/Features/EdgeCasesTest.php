<?php

use App\Models\PlanItem;
use App\Models\PlatformSetting;
use App\Services\OpenLotteryService;

/**
 * What is rare but must hold: many floors, the longest names the forms take, hundreds of
 * families and people. Every page stays within the window (nothing wider than the screen),
 * on a computer and on a phone.
 */

/** The page is no wider than the window: nothing overflows sideways */
function fitsTheWindow(object $page): object
{
    expect($page->script('() => document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();

    return $page;
}

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project, ['name' => substr(str_repeat('Tipo de vivienda grande ', 4), 0, 80)]);
});

it('holds a plan of many floors, read and drawn', function () {
    foreach (range(0, 15) as $floor) {
        $this->project->plan->items()->create(['type' => 'green', 'floor' => $floor, 'polygon' => [[0, 0], [80, 0], [80, 80], [0, 80]]]);
    }
    $this->as($this->admin);

    $page = fitsTheWindow(visit(route('units.index', $this->project))->assertSee('Floor 15'));
    $page->click('[role="tab"]:has-text("Floor 15")')->assertPresent('.plan-beneath');
    fitsTheWindow($page->click('Edit the plan')->assertPresent('[aria-label="Plan editor"]'));
    fitsTheWindow(visit(route('units.index', $this->project))->on()->mobile()->assertSee('Floor 15'));
});

it('holds the longest unit and family names the forms take', function () {
    $long = substr(str_repeat('Vivienda del bloque norte frente a la plaza ', 3), 0, 80);
    $unit = aUnit($this->type, ['identifier' => $long]);
    $family = aFamily($this->project, $this->type, ['name' => substr(str_repeat('Familia Rodríguez Fernández de la Fuente ', 4), 0, 120)]);
    $member = aMemberOf($family);
    $this->as($member);

    foreach (['units.index', 'families.index', 'lottery.show', 'projects.show'] as $place) {
        fitsTheWindow(visit(route($place, $this->project))->assertNoSmoke());
        fitsTheWindow(visit(route($place, $this->project))->on()->mobile());
    }
    fitsTheWindow(visit(route('families.index', ['project' => $this->project, 'family' => $family->id]))->assertPresent('[role="dialog"]'));
    expect(PlanItem::where('unit_id', $unit->id)->exists())->toBeTrue();
});

it('holds a unit name with no spaces to break it at, on the plan and in a ranking', function () {
    $long = mb_substr(str_repeat('Vivienda', 10), 0, 80);
    aUnit($this->type, ['identifier' => $long]);
    $this->as($this->admin);

    $page = fitsTheWindow(visit(route('units.index', $this->project))->assertNoSmoke());
    fitsTheWindow($page->click('Edit the plan')->click("[data-shape=\"{$long}\"] .plan-top")->assertSee("Unit {$long}"));

    $this->as(aMemberOf(aFamily($this->project, $this->type)));
    foreach (['units.index', 'lottery.show'] as $place) {
        fitsTheWindow(visit(route($place, $this->project))->assertNoSmoke());
        fitsTheWindow(visit(route($place, $this->project))->on()->mobile());
    }
});

it('holds the longest names an open lottery takes, with no spaces to break them at', function () {
    config(['variant.name' => 'open_lottery']);
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 0);
    $lotteries = app(OpenLotteryService::class);
    // As long as each field takes, in one word
    $word = fn (int $length, string $start) => mb_substr(str_repeat($start, $length), 0, $length);
    [$lottery, $organizing] = $lotteries->create([
        'name'       => $word(80, 'Vacaciones'),
        'items_name' => $word(40, 'Habitaciones'),
        'items'      => [$word(60, 'Dormitorio'), 'Altillo'],
    ]);
    [, $own] = $lotteries->join($lottery, $word(60, 'Bartolomé'));
    $lotteries->join($lottery, 'Ana');

    // Taking part and organizing it, on a computer and on a phone
    $organizer = route('open.organize', [$lottery, $organizing]);
    $me = route('open.me', [$lottery, $own]);
    fitsTheWindow(visit($organizer)->assertSee('Organizing')->assertNoSmoke());
    fitsTheWindow(visit($organizer)->on()->mobile()->assertSee('Organizing'));
    fitsTheWindow(visit($me)->assertSee('Your preferences')->assertNoSmoke());
    fitsTheWindow(visit($me)->on()->mobile()->assertSee('Your preferences'));

    // Its result
    $lotteries->run($lottery, true);
    fitsTheWindow(visit(route('open.show', $lottery))->assertSee('The result'));
    fitsTheWindow(visit(route('open.show', $lottery))->on()->mobile()->assertSee('The result'));
});

it('holds hundreds of families and people', function () {
    aUnit($this->type);
    foreach (range(1, 230) as $n) {
        $family = aFamily($this->project, $this->type, ['name' => sprintf('Familia %03d', $n)]);
        aMemberOf($family);
        aMemberOf($family);
    }
    $this->as($this->admin);

    fitsTheWindow(visit(route('families.index', $this->project))->assertSee('Familia 001')->assertNoSmoke());
    fitsTheWindow(visit(route('people.index', $this->project))->assertNoSmoke());
    fitsTheWindow(visit(route('projects.show', $this->project))->assertSee('230')->assertNoSmoke());
    fitsTheWindow(visit(route('families.index', $this->project))->on()->mobile()->assertSee('Familia 001'));
})->group('slow');
