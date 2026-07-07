<?php

/**
 * Tests for avatar upload (AvatarController).
 *
 * The endpoint responds with JSON (used by the avatar uploader component).
 */

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses()->group('Feature.Settings');

describe('Updating the avatar', function () {
    it('stores the uploaded image and updates the user', function () {
        Storage::fake('public');
        $this->actingAs(User::find(102));

        $response = $this->post(route('avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'avatar_url']);

        $path = User::find(102)->avatar;
        expect($path)->not->toBeNull();
        Storage::disk('public')->assertExists($path);
    });

    it('deletes the previous avatar when uploading a new one', function () {
        Storage::fake('public');
        $this->actingAs(User::find(102));

        $this->post(route('avatar.update'), ['avatar' => UploadedFile::fake()->image('first.png')]);
        $firstPath = User::find(102)->avatar;

        $this->post(route('avatar.update'), ['avatar' => UploadedFile::fake()->image('second.png')]);

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists(User::find(102)->avatar);
    });

    it('rejects non-image uploads', function () {
        Storage::fake('public');
        $this->actingAs(User::find(102));

        $response = $this->post(route('avatar.update'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('avatar');
        expect(User::find(102)->avatar)->toBeNull();
    });

    it('rejects images over the 2MB size limit', function () {
        Storage::fake('public');
        $this->actingAs(User::find(102));

        $response = $this->post(route('avatar.update'), [
            'avatar' => UploadedFile::fake()->image('huge.png')->size(3000),
        ]);

        $response->assertSessionHasErrors('avatar');
    });
});
