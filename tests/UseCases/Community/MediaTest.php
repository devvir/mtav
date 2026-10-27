<?php

use App\Enums\MediaCategory;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Anyone in a project shares images, videos, audio and documents with the rest of it.
 * A published file cannot be replaced: its owner may edit the description or remove it,
 * and admins may remove any file.
 */

beforeEach(function () {
    Storage::fake(Media::DISK);

    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project));
});

function upload($test, $user, UploadedFile ...$files)
{
    return $test->as($user, $test->project)->post(route('media.store'), [
        'description' => 'Building site, week 12',
        'files'       => $files,
    ]);
}

it('lets a member share an image with the project gallery', function () {
    upload($this, $this->member, UploadedFile::fake()->image('site.jpg', 800, 600))
        ->assertSessionHasNoErrors();

    $media = $this->project->media()->sole();

    expect($media)
        ->category->toBe(MediaCategory::IMAGE)
        ->owner_id->toBe($this->member->id)
        ->description->toBe('Building site, week 12');

    $this->as($this->admin, $this->project)->get(route('media.show', $media))->assertOk();
});

it('files a PDF under documents', function () {
    upload($this, $this->member, UploadedFile::fake()->create('minutes.pdf', 100, 'application/pdf'))
        ->assertSessionHasNoErrors();

    expect($this->project->media()->sole()->category)->toBe(MediaCategory::DOCUMENT);
});

it('shares several files at once', function () {
    upload(
        $this,
        $this->member,
        UploadedFile::fake()->image('one.jpg'),
        UploadedFile::fake()->image('two.png'),
    )->assertSessionHasNoErrors();

    expect($this->project->media()->count())->toBe(2);
});

it('rejects executable files', function () {
    upload($this, $this->member, UploadedFile::fake()->create('setup.exe', 10, 'application/x-msdownload'))
        ->assertSessionHasErrors();

    expect($this->project->media()->exists())->toBeFalse();
});

it('rejects SVG images, which can carry scripts', function () {
    $svg = UploadedFile::fake()->createWithContent(
        'logo.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
    );

    upload($this, $this->member, $svg)->assertSessionHasErrors();

    expect($this->project->media()->exists())->toBeFalse();
});

it('lets the owner edit the description, and nobody else', function () {
    $media = Media::factory()->create(['project_id' => $this->project->id, 'owner_id' => $this->member->id]);
    $neighbour = aMemberOf(aFamily($this->project));

    $this->as($this->member, $this->project)
        ->put(route('media.update', $media), ['description' => 'Better description'])
        ->assertSessionHasNoErrors();

    expect($this->as($neighbour, $this->project)
        ->put(route('media.update', $media), ['description' => 'Vandalised']))->toBeDenied();

    expect($media->fresh()->description)->toBe('Better description');
});

it('lets the owner and admins remove a file, and nobody else', function () {
    $mine = Media::factory()->create(['project_id' => $this->project->id, 'owner_id' => $this->member->id]);
    $theirs = Media::factory()->create(['project_id' => $this->project->id, 'owner_id' => $this->member->id]);
    $neighbour = aMemberOf(aFamily($this->project));

    expect($this->as($neighbour, $this->project)->delete(route('media.destroy', $mine)))->toBeDenied();
    expect(Media::find($mine->id))->not->toBeNull();

    $this->as($this->member, $this->project)->delete(route('media.destroy', $mine));
    $this->as($this->admin, $this->project)->delete(route('media.destroy', $theirs));

    expect(Media::find($mine->id))->toBeNull()
        ->and(Media::find($theirs->id))->toBeNull();
});

it('keeps a project\'s files away from other projects and from guests', function () {
    upload($this, $this->member, UploadedFile::fake()->image('site.jpg'));
    $media = $this->project->media()->sole();
    $outsider = aMemberOf(aFamily(aProject()));

    $this->as($this->member, $this->project)->get(route('media.file', $media))->assertOk();

    expect($this->as($outsider)->get(route('media.show', [$this->project, $media])))->toBeDenied()
        ->and($this->as($outsider)->get(route('media.file', [$this->project, $media])))->toBeDenied();

    auth()->logout();
    $this->get(route('media.file', [$this->project, $media]))->assertRedirect(route('login'));
});

it('limits the storage of an unapproved project', function () {
    config()->set('limits.unapproved.media_storage_mb', 1);
    $project = anUnapprovedProject();
    $this->project = $project;
    $admin = $project->admins()->first();

    upload($this, $admin, UploadedFile::fake()->image('big.jpg')->size(800))->assertSessionHasNoErrors();
    upload($this, $admin, UploadedFile::fake()->image('more.jpg')->size(800))->assertSessionHasErrors('files');

    expect($project->media()->count())->toBe(1);
});

it('limits the file size of an unapproved project', function () {
    config()->set('limits.unapproved.media_file_mb', 1);
    $project = anUnapprovedProject();
    $this->project = $project;

    upload($this, $project->admins()->first(), UploadedFile::fake()->image('big.jpg')->size(1500))
        ->assertSessionHasErrors('files.0');
});
