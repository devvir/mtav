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
    attachFile(visit(route('media.create', $this->project)), 'input[type="file"]', pngFile())
        ->assertSee('photo.png')
        ->fill('description', 'Building site, week 12')
        ->click('Share')
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
    attachFile(visit(route('media.create', $this->project)), 'input[type="file"]', fixtureFile('minutes.txt', 'Minutes of the assembly'))
        ->fill('description', 'Assembly minutes')
        ->click('Share')
        ->assertSee('Assembly minutes')
        ->click('Assembly minutes')
        ->assertAttributeContains('a:has-text("Download the document")', 'href', '/file');

    expect(Media::sole()->category->value)->toBe('document');
});

it('refuses files that could run scripts', function () {
    attachFile(visit(route('media.create', $this->project)), 'input[type="file"]', fixtureFile('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))
        ->assertSee('logo.svg cannot be shared')
        ->fill('description', 'A logo')
        ->click('Share')
        ->assertSee('The files field is required.');

    // The server refuses them too (UseCases\Community\MediaTest)
    expect(Media::count())->toBe(0);
});

it('shows how much storage an unapproved project uses', function () {
    $project = anUnapprovedProject();
    $this->as($project->admins()->first());

    visit(route('media.create', $project))->assertSee('The project uses 0 of its 200 MB until it is approved.');
});
