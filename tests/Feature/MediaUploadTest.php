<?php

use App\Models\Media;
use App\Models\User;
use App\Services\MediaUploader;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Media::DISK);
});

/**
 * The private disk is configured with `throw => false, report => false`, so a failed write
 * returns false instead of raising. An unwritable storage directory must not produce a 201
 * and a media row pointing at bytes that were never stored.
 */
it('does not record media when the bytes could not be written', function () {
    $user = User::factory()->create();
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('putFileAs')->once()->andReturn(false);
    Storage::shouldReceive('disk')->with(Media::DISK)->andReturn($disk);

    expect(fn () => app(MediaUploader::class)->store($user, UploadedFile::fake()->create('a.txt', 1)))
        ->toThrow(RuntimeException::class);

    expect(Media::count())->toBe(0);
});

it('redirects guests to the login page', function () {
    $this->post('/media', ['file' => UploadedFile::fake()->create('a.txt', 1)])->assertRedirect('/login');
});

it('accepts a session upload from the editor and returns the same JSON shape', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/media', ['file' => UploadedFile::fake()->create('a.txt', 1, 'text/plain')])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'url', 'name', 'mime_type', 'size', 'markdown']]);

    expect(Media::sole()->user_id)->toBe($user->id);
});

it('applies the same blocklist to session uploads', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/media', ['file' => UploadedFile::fake()->create('a.exe', 1)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});
