<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * A person's picture: an image, kept small and anew (nothing of the original's metadata).
 */
beforeEach(function () {
    Storage::fake('public');
    $this->user = aUser();
});

it('keeps a picture small, drawn anew as WebP', function () {
    $this->as($this->user)->post(route('avatar.update'), [
        'avatar' => UploadedFile::fake()->image('me.jpg', 1600, 1200),
    ])->assertSessionHasNoErrors();

    $path = $this->user->fresh()->avatar;
    [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($path));
    expect([$width, $height, $type])->toBe([512, 384, IMAGETYPE_WEBP])->and($path)->toEndWith('.webp');
});

it('refuses what is not an image, and an image of more pixels than can be opened', function () {
    $this->as($this->user)->post(route('avatar.update'), [
        'avatar' => UploadedFile::fake()->create('me.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('avatar');
    $this->as($this->user)->post(route('avatar.update'), ['avatar' => aPixelBomb()])
        ->assertSessionHasErrors('avatar');

    expect($this->user->fresh()->avatar)->toBeNull();
});

it('lets go of the previous picture', function () {
    $this->as($this->user)->post(route('avatar.update'), ['avatar' => UploadedFile::fake()->image('one.png', 100, 100)]);
    $first = $this->user->fresh()->avatar;
    $this->as($this->user)->post(route('avatar.update'), ['avatar' => UploadedFile::fake()->image('two.png', 100, 100)]);

    Storage::disk('public')->assertMissing($first);
});
