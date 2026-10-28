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

/** A real file on disk for the browser to upload */
function fixtureFile(string $name, string $content): string
{
    $path = sys_get_temp_dir() . '/' . $name;
    file_put_contents($path, $content);

    return $path;
}

function pngFile(string $name = 'photo.png'): string
{
    $image = imagecreatetruecolor(64, 48);
    imagefill($image, 0, 0, imagecolorallocate($image, 30, 120, 200));
    ob_start();
    imagepng($image);

    return fixtureFile($name, ob_get_clean());
}

it('shares a photo, then edits and removes it', function () {
    attachFile(visit(route('media.create', $this->project)), 'input[type="file"]', pngFile())
        ->assertSee('photo.png')
        ->fill('description', 'Building site, week 12')
        ->click('Share')
        ->assertSee('Your file is shared.')
        ->click('img[alt="Building site, week 12"]')
        ->assertSee('Shared by')
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
        ->assertSee('Assembly minutes');

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
