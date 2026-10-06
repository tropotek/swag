<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use App\Services\MediaUploader;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
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

/**
 * APP_DEBUG is on in development and the editor sends Accept: application/json, so an
 * uncaught exception comes back as JSON whose `message` is the raw SQL or filesystem error.
 * The editor must never show that: the user gets one plain sentence, the detail goes to the
 * log, and only a development build prints it to the console.
 */
it('shows a plain message, not the underlying error, when recording the upload fails', function () {
    config(['app.debug' => true]);
    $user = User::factory()->create();
    Schema::drop('media');

    $response = $this->actingAs($user)
        ->postJson('/media', ['file' => UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg')])
        ->assertStatus(500);

    expect($response->json('errors.file.0'))->toBe("Couldn't save the file. Please try again.")
        ->and(json_encode($response->json()))->not->toContain('no such table')
        ->and(json_encode($response->json()))->not->toContain('SQLSTATE');
});

it('does not leave the written bytes behind when recording the upload fails', function () {
    $user = User::factory()->create();
    Schema::drop('media');

    $this->actingAs($user)
        ->postJson('/media', ['file' => UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg')])
        ->assertStatus(500);

    expect(Storage::disk(Media::DISK)->allFiles('media'))->toBe([]);
});

it('still shows the validation message for a rejected file type', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/media', ['file' => UploadedFile::fake()->create('a.exe', 1)])
        ->assertStatus(422)
        ->assertJsonPath('errors.file.0', "This file type isn't allowed. Put it in a zip file and upload that.");
});

/** The editor refuses an oversized file before uploading it, so it needs the server's limit. */
it('passes the upload size limit to the editor', function () {
    config(['swag.media.max_kb' => 25600]);
    $user = User::factory()->create();
    $page = Page::factory()->for($user)->create();

    $this->actingAs($user)->get(route('pages.edit', $page))->assertSee('data-max-kb="25600"', false);
});

it('tells the editor whether to log detail to the console, following app.debug', function (bool $debug, string $expected) {
    config(['app.debug' => $debug]);
    $user = User::factory()->create();
    $page = Page::factory()->for($user)->create();

    $this->actingAs($user)->get(route('pages.edit', $page))->assertSee('data-debug="'.$expected.'"', false);
})->with([[true, '1'], [false, '0']]);

it('applies the same blocklist to session uploads', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/media', ['file' => UploadedFile::fake()->create('a.exe', 1)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});
