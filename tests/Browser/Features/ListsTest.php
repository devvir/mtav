<?php

use App\Enums\MediaCategory;
use App\Models\Media;
use App\Services\ProjectService;

/**
 * Lists: searching them, and the tabs that split them.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->type = aUnitType($this->project, ['name' => 'Studio']);
    $this->garcia = aFamily($this->project, $this->type, ['name' => 'García']);
    $this->perez = aFamily($this->project, $this->type, ['name' => 'Pérez']);
    $this->ana = aMemberOf($this->garcia, ['firstname' => 'Ana', 'lastname' => 'García']);
    $this->bruno = aMemberOf($this->perez, ['firstname' => 'Bruno', 'lastname' => 'Pérez']);
    $this->admin = anAdminOf($this->project);
});

it('finds families by their name or their people\'s', function () {
    $this->as($this->ana);

    visit(route('families.index', $this->project))
        ->type('[aria-label="Search families or people"]', 'Pér')
        ->assertSee('Pérez')
        ->assertDontSee('García');

    // By a person's name, from a link with the query (typing is checked above)
    visit(route('families.index', [$this->project, 'q' => 'Ana']))
        ->assertValue('[aria-label="Search families or people"]', 'Ana')
        ->assertSee('García')
        ->assertDontSee('Pérez');
});

it('finds people by name', function () {
    $this->as($this->ana);

    visit(route('people.index', $this->project))
        ->type('[aria-label="Search by name"]', 'Bruno')
        ->assertSee('Bruno Pérez')
        ->assertDontSee('Ana García');
});

it('splits events into upcoming and past', function () {
    anEvent($this->project, ['title' => 'Next assembly', 'start_date' => now()->addWeek()]);
    anEvent($this->project, ['title' => 'Last assembly', 'start_date' => now()->subWeek()]);
    $this->as($this->ana);

    visit(route('events.index', $this->project))
        ->assertSee('Next assembly')
        ->assertDontSee('Last assembly')
        ->click('Past')
        ->assertSee('Last assembly')
        ->assertDontSee('Next assembly')
        ->click('Upcoming')
        ->assertSee('Next assembly');
});

it('splits what is shared into media and files', function () {
    Media::factory()->for($this->project)->create(['description' => 'Building site', 'owner_id' => $this->bruno->id]);
    Media::factory()->for($this->project)->create([
        'description' => 'Assembly minutes',
        'category'    => MediaCategory::DOCUMENT,
        'mime_type'   => 'application/pdf',
        'path'        => 'projects/0/minutes.pdf',
        'width'       => null,
        'height'      => null,
        'owner_id'    => $this->bruno->id,
    ]);
    $this->as($this->ana);

    visit(route('media.index', $this->project))
        ->assertSee('Building site')
        ->assertDontSee('Assembly minutes')
        ->click('.ds-pills >> text=Files')
        ->assertSee('Assembly minutes')
        ->assertDontSee('Building site');
});

it('finds people, and shows units by type', function () {
    $loft = aUnitType($this->project, ['name' => 'Loft']);
    aUnit($this->type, ['identifier' => 'A-101']);
    aUnit($loft, ['identifier' => 'L-201']);
    $this->as($this->admin);

    visit(route('people.index', $this->project))
        ->type('[aria-label="Search by name"]', 'Bruno')
        ->assertSee('Bruno Pérez')
        ->assertDontSee('Ana García');

    visit(route('units.index', $this->project))
        ->assertSeeIn('.ds-panel:has-text("Loft")', 'L-201')
        ->assertDontSeeIn('.ds-panel:has-text("Loft")', 'A-101');
});

it('shows superadmins the projects awaiting approval apart', function () {
    $waiting = anUnapprovedProject();
    app(ProjectService::class)->requestApproval($waiting, $waiting->admins()->first());
    $this->as(aSuperadmin());

    visit(route('admin.projects.index'))
        ->click('.ds-pills >> text=Awaiting approval')
        ->assertSee($waiting->name)
        ->assertDontSee($this->project->name);
});
