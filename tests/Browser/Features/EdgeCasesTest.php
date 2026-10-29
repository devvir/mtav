<?php

use App\Models\PlanItem;

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
