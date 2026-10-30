<?php

uses()->group('critical');

use App\Models\Family;
use App\Models\Project;
use App\Models\Unit;
use App\Services\Lottery\PreferencesService;
use Illuminate\Support\Facades\Notification;

/**
 * What the thesis tutor tried on the first version, as the app does it now: a superadmin
 * starts a project and hands it to an admin who already has an account; the admin sets up
 * a small cooperative (one type, or two), families with or without people yet, people who
 * rank or do not; the lottery runs, and every family gets a unit of its type.
 */

/** The admin of a project runs its lottery from the page, as they would */
function runTheLottery(object $page): object
{
    return $page->click('Run the lottery')
        ->fill('[role="dialog"] input', 'RUN')
        ->keys('[role="dialog"] input', 'Enter');
}

it('lets a superadmin hand a project to an admin, who sets it up and runs its lottery', function () {
    Notification::fake();
    $tutor = aUser(['firstname' => 'Hugo', 'lastname' => 'Tutor', 'email' => 'tutor@example.com']);
    $this->as(aSuperadmin());

    // The superadmin starts the project, and invites an existing account as its admin
    visit(route('projects.create'))
        ->fill('name', 'H-18octubre')
        ->fill('description', 'Three homes, three families')
        ->click('Start the project')
        ->assertSee('Getting to the lottery');
    $project = Project::firstWhere('name', 'H-18octubre');

    visit(route('manage.invitations.index', $project))
        ->fill('email', 'tutor@example.com')
        ->check('is_admin')
        ->click('Send the invitation')
        ->assertSee('Invitation sent to tutor@example.com.');

    // The admin finds the invitation among their projects, and joins
    $this->as($tutor);
    visit(route('projects.index'))
        ->assertSee('H-18octubre')
        ->click('Join')
        ->assertSee('H-18octubre');
    expect($tutor->fresh()->manages($project))->toBeTrue();

    // One unit type, its three units (the only type, already chosen), three families
    visit(route('units.index', $project))
        ->click('Add the first unit type')
        ->fill('name', '2 dormitorios')
        ->click(inDialog('Add the unit type'))
        ->assertSee('The unit type was added.')
        ->click('Add the first unit')
        ->fill('identifier', 'U-1')
        ->click('Add and continue with another')
        ->assertSee('The unit was added.')
        ->assertValue('[name="identifier"]', '')
        ->fill('identifier', 'U-2')
        ->click('Add and continue with another')
        ->assertSeeIn('.ds-panel', 'U-2')
        ->assertValue('[name="identifier"]', '')
        ->fill('identifier', 'U-3')
        ->click(inDialog('Add the unit'))
        ->assertSeeIn('.ds-panel', 'U-3');
    expect(Unit::where('project_id', $project->id)->whereNotNull('unit_type_id')->count())->toBe(3);

    foreach (['F01', 'F02', 'F03'] as $name) {
        visit(route('families.index', [$project, 'new' => 1]))
            ->fill('name', $name)
            ->click(inDialog('Add the family'))
            ->assertSee('The family was added.');
    }

    // Families with no one in them yet are there, on their page and on the project's home
    visit(route('families.index', $project))->assertSee('F01')->assertSee('F02')->assertSee('F03');
    visit(route('projects.show', $project))->assertSeeIn('.stat-link:first-child', '3');

    // One person per family; only F01's ranks its units
    $families = Family::where('project_id', $project->id)->orderBy('name')->get();
    // (joining from an invitation is the cooperative journey's)
    $people = $families->map(fn (Family $family) => aMemberOf($family));
    $last = app(PreferencesService::class)->ranking($families[0])->last();
    $this->as($people[0]);
    visit(route('lottery.show', $project))
        ->click("[aria-label=\"Move {$last->identifier} up\"]")
        ->click("[aria-label=\"Move {$last->identifier} up\"]")
        ->wait(2);
    expect($families[0]->preferences()->first()?->id)->toBe($last->id);

    // The admin dates it (today, at midnight: already due) and runs it: every family gets a unit
    $this->as($tutor);
    $page = pickDay(visit(route('lottery.show', $project)), 'start_date', now())
        ->click('[data-tour="lottery.schedule"] button[type="submit"]')
        ->assertSee('The lottery was scheduled');
    runTheLottery($page)->assertSee('The lottery is done: every family has its unit.')->assertNoSmoke();

    // (whose first choice it is depends on the others' rankings: each family gets a unit)
    expect($families->map(fn ($family) => $family->fresh()->unit?->id)->filter()->unique())->toHaveCount(3);

    // Every one of them sees the outcome
    $this->as($people[1]);
    visit(route('lottery.show', $project))->assertSee('The lottery is done')->assertSee('F02');
});

it('runs a lottery of two types, every family ranking, each family within its type', function () {
    $project = aProject(['name' => 'H-19octubre']);
    $admin = anAdminOf($project);
    $types = collect(['1 dormitorio', '2 dormitorios'])->map(fn ($name) => aUnitType($project, ['name' => $name]));
    $this->as($admin);

    // Units of either type, the type picked among two
    visit(route('units.index', $project))
        ->click('Add units')
        ->fill('identifier', 'A-1')
        ->select('unit_type_id', (string) $types[1]->id)
        ->click(inDialog('Add the unit'))
        ->assertSee('The unit was added.');
    expect(Unit::firstWhere('identifier', 'A-1')->unit_type_id)->toBe($types[1]->id);
    aUnit($types[0], ['identifier' => 'B-1']);
    aUnit($types[0], ['identifier' => 'B-2']);
    aUnit($types[1], ['identifier' => 'A-2']);

    $families = $types->flatMap(fn ($type) => collect([1, 2])->map(function () use ($project, $type) {
        $family = aFamily($project, $type);
        aMemberOf($family);
        $ranking = app(PreferencesService::class)->ranking($family);
        app(PreferencesService::class)->rank($family, $ranking->reverse()->pluck('id')->all());

        return $family;
    }));
    $project->lottery->update(['start_date' => now()->subMinute()]);

    runTheLottery(visit(route('lottery.show', $project)))
        ->assertSee('The lottery is done: every family has its unit.');

    foreach ($families as $family) {
        expect($family->fresh()->unit?->unit_type_id)->toBe($family->unit_type_id);
    }
});
