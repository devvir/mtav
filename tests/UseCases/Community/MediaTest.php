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

    $this->as($this->admin, $this->project)->get(route('media.show', $media))
        ->assertRedirect(route('media.index', ['file' => $media->id]));

    expect($this->get(route('media.index', ['file' => $media->id]))->inertiaProps('file.id'))->toBe($media->id);
});

it('files a PDF under documents', function () {
    upload($this, $this->member, UploadedFile::fake()->create('minutes.pdf', 100, 'application/pdf'))
        ->assertSessionHasNoErrors();

    expect($this->project->media()->sole()->category)->toBe(MediaCategory::DOCUMENT);
});

it('files a phone\'s voice memo under audio', function () {
    upload($this, $this->member, UploadedFile::fake()->create('memo.m4a', 300, 'audio/x-m4a'))
        ->assertSessionHasNoErrors();

    expect($this->project->media()->sole()->category)->toBe(MediaCategory::AUDIO);
});

it('lists recordings with the documents, as the project\'s records, and the gallery with photos and videos only', function () {
    $media = fn ($category, $mime) => Media::factory()->create(['project_id' => $this->project->id, 'category' => $category, 'mime_type' => $mime]);
    $photo = $media(MediaCategory::IMAGE, 'image/jpeg');
    $video = $media(MediaCategory::VIDEO, 'video/mp4');
    $recording = $media(MediaCategory::AUDIO, 'audio/mpeg');
    $minutes = $media(MediaCategory::DOCUMENT, 'application/pdf');
    $this->as($this->member, $this->project);

    $listed = fn (array $query) => collect($this->get(route('media.index', $query))->inertiaProps('media.data'))->pluck('id')->sort()->values()->all();

    expect($listed([]))->toBe([$photo->id, $video->id])
        ->and($listed(['category' => 'files']))->toBe([$recording->id, $minutes->id]);

    // A recording shared alone, or removed, leads back to the files
    upload($this, $this->member, UploadedFile::fake()->create('memo.m4a', 300, 'audio/x-m4a'))
        ->assertRedirect(route('media.index', ['category' => 'files']));
    $this->delete(route('media.destroy', $recording))->assertRedirect(route('media.index', ['category' => 'files']));
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

it('moves from photo to photo past the page they are listed on', function () {
    // 30 photos, a minute apart: the list shows 24 a page, newest first
    $photos = collect(range(1, 30))->map(fn ($minute) => Media::factory()->for($this->project)->create([
        'owner_id' => $this->member->id, 'created_at' => now()->subMinutes(100 - $minute),
    ]))->reverse()->values();

    $this->as($this->member, $this->project);
    $carousel = $this->get(route('media.index', ['file' => $photos[23]->id]))->inertiaProps('carousel');

    expect($carousel)->toBe(['position' => 24, 'count' => 30, 'previous' => $photos[22]->id, 'next' => $photos[24]->id]);
});

it('says so when a share is more than one request may carry', function () {
    $this->as($this->member, $this->project)
        ->from(route('media.index'))
        ->call('POST', route('media.store'), server: ['CONTENT_LENGTH' => 10 ** 12])
        ->assertRedirect(route('media.index'))
        ->assertSessionHas('error', __('flash.upload_too_large'));
});

it('refuses an image of more pixels than can be opened, small as its file is', function () {
    upload($this, $this->member, aPixelBomb())->assertSessionHasErrors('files.0');

    expect(Media::count())->toBe(0);
});

it('gives each stored file its own address, never one a cache could answer with another file of that number', function () {
    $media = Media::factory()->create(['project_id' => $this->project->id, 'thumbnail' => 'projects/0/a.thumb.jpg']);
    $before = (new App\Http\Resources\MediaResource($media))->resolve();

    // Data started over: the same number, another file
    $media->update(['path' => 'projects/0/another.jpg', 'thumbnail' => 'projects/0/another.thumb.jpg']);
    $after = (new App\Http\Resources\MediaResource($media->fresh()))->resolve();

    expect($after['url'])->not->toBe($before['url'])
        ->and($after['thumbnail'])->not->toBe($before['thumbnail']);

    // Either address still serves the file
    Storage::disk(Media::DISK)->put('projects/0/another.jpg', 'jpeg');
    $this->as($this->member, $this->project)->get($after['url'])->assertOk();
});
