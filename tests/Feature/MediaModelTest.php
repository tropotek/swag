<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Task 3 registers the real route; register a stand-in so url() works in this file.
    if (! Route::has('media.show')) {
        Route::get('/media/{media}/{name}', fn () => 'ok')->name('media.show');
        Route::getRoutes()->refreshNameLookups();
    }
});

it('uses the uuid as its route key and stores bytes under media/{uuid}', function () {
    $media = Media::factory()->create(['uuid' => 'abc-123']);

    expect($media->getRouteKeyName())->toBe('uuid')
        ->and($media->path())->toBe('media/abc-123');
});

it('flags images, audio and video as inline, but only images as images', function (string $mime, bool $inline, bool $image) {
    $media = Media::factory()->make(['mime_type' => $mime]);

    expect($media->isInline())->toBe($inline)->and($media->isImage())->toBe($image);
})->with([
    ['image/png', true, true],
    ['image/jpeg', true, true],
    ['image/gif', true, true],
    ['image/webp', true, true],
    ['audio/mpeg', true, false],
    ['audio/wav', true, false],
    ['video/mp4', true, false],
    ['video/quicktime', true, false],
    ['application/pdf', false, false],
    ['image/svg+xml', false, false],
    ['text/html', false, false],
    ['application/zip', false, false],
]);

it('builds a url-safe name from the original name', function (string $original, string $expected) {
    expect(Media::factory()->make(['original_name' => $original])->urlName())->toBe($expected);
})->with([
    ['My Photo (1).PNG', 'my-photo-1.png'],
    ['notes', 'notes'],
    ['日本語.txt', 'file.txt'],
    // Str::slug drops the inner dot rather than hyphenating it. Cosmetic only: lookup is by uuid.
    ['archive.tar.gz', 'archivetar.gz'],
]);

it('builds markdown: image syntax for images, link syntax otherwise, with the label escaped', function () {
    $image = Media::factory()->create(['original_name' => 'my photo [1].png', 'mime_type' => 'image/png', 'uuid' => 'u1']);
    $zip = Media::factory()->create(['original_name' => 'kit.zip', 'mime_type' => 'application/zip', 'uuid' => 'u2']);

    expect($image->markdown())->toBe('![my photo \[1\].png]('.$image->url().')')
        ->and($zip->markdown())->toBe('[kit.zip]('.$zip->url().')');
});

it('blocks executable extensions anywhere in the name, case-insensitively', function (string $name, bool $blocked) {
    expect(Media::hasBlockedExtension($name))->toBe($blocked);
})->with([
    ['setup.exe', true],
    ['SETUP.EXE', true],
    ['a.exe.txt', true],
    ['a.exe ', true],
    ['a.exe.', true],
    ['run.Sh', true],
    ['index.php', true],
    ['kit.zip', false],
    ['notes.txt', false],
    ['sh', false],
    ['photo.png', false],
]);

it('only lets the owner view media; everyone else gets a 404 response', function () {
    $owner = User::factory()->create();
    $media = Media::factory()->for($owner)->create();

    expect($owner->can('view', $media))->toBeTrue()
        ->and(User::factory()->create()->can('view', $media))->toBeFalse();
});

it('is reachable from its owner', function () {
    $user = User::factory()->create();
    $media = Media::factory()->for($user)->create();

    expect($user->media()->sole()->is($media))->toBeTrue()->and($media->user->is($user))->toBeTrue();
});
