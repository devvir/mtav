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
    $page = attachFile(visit(route('media.index', [$this->project, 'share' => 1])), 'input[type="file"]', pngFile())
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
        ->assertSee('The description was saved.');

    // Its own address (a notification's link to it) opens it over the list
    visit(route('media.show', [$this->project, Media::sole()]))
        ->assertSee('Shared by')
        ->assertSee('Building site, week 13')
        ->assertNoSmoke();

    $page->click('Remove')
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

it('lists recordings with the files, each playing in its row at the speed chosen', function () {
    Media::factory()->create([
        'project_id'  => $this->project->id, 'category' => 'audio', 'mime_type' => 'audio/mpeg',
        'description' => 'Summary of the last assembly', 'width' => null, 'height' => null,
    ]);

    visit(route('media.index', $this->project))
        ->assertDontSee('Summary of the last assembly')
        ->click('Files')
        ->assertSee('Summary of the last assembly')
        ->assertPresent('[data-audio] button[aria-label="Play"]')
        ->click('[data-audio] button[aria-label^="Speed"]')
        ->assertSeeIn('[data-audio]', '1.25×')
        ->assertNoSmoke();
});

it('refuses files that could run scripts', function () {
    attachFile(visit(route('media.index', [$this->project, 'share' => 1])), 'input[type="file"]', fixtureFile('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))
        ->assertSee('logo.svg cannot be shared')
        ->fill(inDialogField('description'), 'A logo')
        ->click(inDialog('Share'))
        ->assertSee('Fill in the files.');

    // The server refuses them too (UseCases\Community\MediaTest)
    expect(Media::count())->toBe(0);
});

it('shows how much storage an unapproved project uses', function () {
    $project = anUnapprovedProject();
    $this->as($project->admins()->first());

    visit(route('media.index', $project))
        ->click('Share files')
        ->assertSeeIn('[role="dialog"]', 'The project uses 0 MB of its 500 MB.');
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

/** Files chosen together, as a person picks several: each `bytes` long, made in the browser */
function chooseFiles(object $page, array $files): object
{
    $files = json_encode($files);

    $page->script(<<<JS
        () => {
            const input = document.querySelector('input[type="file"]');
            // The plugin may run a script again after a slow first try: choose once
            if (input.dataset.chosen) return;
            input.dataset.chosen = '1';
            const transfer = new DataTransfer();
            {$files}.forEach(({ name, type, bytes }) =>
                transfer.items.add(new File([new Uint8Array(bytes)], name, { type })));
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        JS);

    return $page;
}

it('says which files were refused when others came with them', function () {
    chooseFiles(visit(route('media.index', [$this->project, 'share' => 1])), [
        ['name' => 'site-1.png', 'type' => 'image/png', 'bytes' => 10],
        ['name' => 'site-2.png', 'type' => 'image/png', 'bytes' => 10],
        ['name' => 'logo.svg', 'type' => 'image/svg+xml', 'bytes' => 10],
    ])
        ->assertSee('logo.svg cannot be shared')
        ->assertSee('site-1.png')
        ->assertSee('site-2.png')
        ->assertNoSmoke();
});

it('takes as many files as one share may have, and says the rest were left out', function () {
    $files = array_map(fn ($n) => ['name' => "minutes-{$n}.txt", 'type' => 'text/plain', 'bytes' => 10], range(1, 21));

    $page = chooseFiles(visit(route('media.index', [$this->project, 'share' => 1])), $files)
        ->assertSee('Up to 20 at once: the rest were left out.')
        ->assertSee('minutes-20.txt')
        ->assertDontSee('minutes-21.txt');

    expect($page->script('() => document.querySelectorAll(".ds-file").length'))->toBe(20);
});

it('shares nothing while one of the files is too large, until it is removed', function () {
    config()->set('limits.media_file_max_mb', 1);

    $page = chooseFiles(visit(route('media.index', [$this->project, 'share' => 1])), [
        ['name' => 'site.png', 'type' => 'image/png', 'bytes' => 10],
        ['name' => 'plans.pdf', 'type' => 'application/pdf', 'bytes' => 1_500_000],
    ])
        ->assertSee('Too large')
        ->fill(inDialogField('description'), 'Building site, week 12');
    $shareOff = '() => document.querySelector(\'[role="dialog"] button[type="submit"]\').disabled';

    expect($page->script($shareOff))->toBeTrue();

    $page->click('[aria-label="Remove plans.pdf"]')->assertDontSee('Too large');

    expect($page->script($shareOff))->toBeFalse();
});
