<?php

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Files: sharing, viewing, editing, removing; what is refused.
 */

beforeEach(function () {
    Storage::fake(Media::DISK);

    $this->project = aProject();
    $this->member = aMemberOf(aFamily($this->project));
    $this->as($this->member);
});

it('shares a photo, then edits and removes it', function () {
    attachFile(visit(route('media.index', [$this->project, 'share' => 1])), 'input[type="file"]', pngFile())
        ->assertSee('photo.png')
        ->fill(inDialogField('description'), 'Building site, week 12')
        ->click(inDialog('Share'))
        ->assertSee('Your file is shared.')
        ->click('img[alt="Building site, week 12"]')
        ->assertSee('Shared by')
        ->assertAttributeContains('a:has-text("Download")', 'href', '/file')
        ->click('Edit')
        ->fill('description', 'Building site, week 13')
        ->click('Save')
        ->assertSee('The description was saved.')
        ->click('Remove')
        ->click(inDialog('Remove'))
        ->assertSee('The file was removed.')
        ->assertNoSmoke();

    expect(Media::count())->toBe(0);
});

it('files documents under documents', function () {
    attachFile(visit(route('media.index', [$this->project, 'share' => 1])), 'input[type="file"]', fixtureFile('minutes.txt', 'Minutes of the assembly'))
        ->fill(inDialogField('description'), 'Assembly minutes')
        ->click(inDialog('Share'))
        ->assertSee('Assembly minutes')
        ->click('Assembly minutes')
        ->assertAttributeContains('a:has-text("Download the document")', 'href', '/file');

    expect(Media::sole()->category->value)->toBe('document');
});

it('refuses files that could run scripts', function () {
    attachFile(visit(route('media.index', [$this->project, 'share' => 1])), 'input[type="file"]', fixtureFile('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))
        ->assertSee('logo.svg cannot be shared')
        ->fill(inDialogField('description'), 'A logo')
        ->click(inDialog('Share'))
        ->assertSee('The files field is required.');

    // The server refuses them too (UseCases\Community\MediaTest)
    expect(Media::count())->toBe(0);
});

it('shows how much storage an unapproved project uses', function () {
    $project = anUnapprovedProject();
    $this->as($project->admins()->first());

    visit(route('media.index', $project))
        ->click('Share files')
        ->assertSeeIn('[role="dialog"]', 'The project uses 0 of its 200 MB until it is approved.');
});

it('goes from one photo to the next with the arrows, the keyboard and back', function () {
    foreach (['First photo', 'Second photo', 'Third photo'] as $at => $description) {
        Media::factory()->for($this->project)->create([
            'description' => $description,
            'owner_id'    => $this->member->id,
            'created_at'  => now()->subMinutes(10 - $at),
        ]);
    }
    $newest = Media::firstWhere('description', 'Third photo');

    visit(route('media.index', [$this->project, 'file' => $newest->id]))
        ->assertSeeIn('[role="dialog"]', '1 of 3')
        ->assertNotPresent('[aria-label="Previous"]')
        ->click('[aria-label="Next"]')
        ->assertSeeIn('[role="dialog"]', 'Second photo')
        ->assertSeeIn('[role="dialog"]', '2 of 3')
        ->keys('[role="dialog"]', 'ArrowRight')
        ->assertSeeIn('[role="dialog"]', 'First photo')
        ->assertNotPresent('[aria-label="Next"]')
        ->click('[aria-label="Previous"]')
        ->assertSeeIn('[role="dialog"]', 'Second photo')
        ->assertQueryStringHas('file', (string) Media::firstWhere('description', 'Second photo')->id);
});
