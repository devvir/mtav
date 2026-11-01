<?php

use App\Enums\MediaCategory;
use App\Models\Media;
use App\Services\ForumService;
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

it('narrows people to a family, or to the admins', function () {
    $this->as($this->ana);

    visit(route('people.index', $this->project))
        ->select('[aria-label="Family"]', (string) $this->perez->id)
        ->assertSee('Bruno Pérez')
        ->assertDontSee('Ana García')
        ->assertQueryStringHas('family', (string) $this->perez->id);

    visit(route('people.index', $this->project))
        ->check('admins')
        ->assertQueryStringHas('admins', '1')
        ->assertDontSee('Bruno Pérez');
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
        ->type('[aria-label="Search by name or email"]', 'Bruno')
        ->assertSee('Bruno Pérez')
        ->assertDontSee('Ana García');

    visit(route('units.index', $this->project))
        ->assertSeeIn('.units-by-type-row:has-text("Loft")', 'L-201')
        ->assertDontSeeIn('.units-by-type-row:has-text("Loft")', 'A-101');
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

it('leaves the live list\'s pager out while the archive is shown', function () {
    $forum = app(ForumService::class);
    $this->as($this->admin);
    foreach (range(1, 31) as $n) {
        $topic = $forum->open($this->project, $this->admin, "Topic {$n}", 'About it');
    }
    $topic->delete();
    foreach (range(32, 33) as $n) {
        $forum->open($this->project, $this->admin, "Topic {$n}", 'About it');
    }

    visit(route('forum.index', $this->project))
        ->assertPresent('nav[aria-label="Pages"]')
        ->click('label:has-text("Show archived")')
        ->assertSee('Topic 31')
        ->assertNotPresent('nav[aria-label="Pages"]')
        ->click('label:has-text("Show archived")')
        ->assertPresent('nav[aria-label="Pages"]');
});

it('leaves it out on the files and the families too', function () {
    config(['lists.whole' => 1]);
    $this->as($this->admin);
    Media::factory(25)->for($this->project)->create(['owner_id' => $this->admin->id])->first()->delete();
    Media::factory(25)->for($this->project)->create(['owner_id' => $this->admin->id]);
    foreach (range(1, 30 + 1) as $n) {
        aFamily($this->project, $this->type, ['name' => "Family {$n}"]);
    }
    aFamily($this->project, $this->type, ['name' => 'Gone'])->delete();

    visit(route('media.index', $this->project))
        ->assertPresent('nav[aria-label="Pages"]')
        ->click('label:has-text("Show archived")')
        ->assertNotPresent('nav[aria-label="Pages"]');

    visit(route('families.index', $this->project))
        ->assertPresent('nav[aria-label="Pages"]')
        ->click('label:has-text("Show archived")')
        ->assertSee('Gone')
        ->assertNotPresent('nav[aria-label="Pages"]');
});
