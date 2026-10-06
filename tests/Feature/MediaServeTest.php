<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Media::DISK);
});

function storedMedia(User $user, string $name, string $mime): Media
{
    $media = Media::factory()->for($user)->create(['original_name' => $name, 'mime_type' => $mime]);
    Storage::disk(Media::DISK)->put($media->path(), 'file-bytes');

    return $media;
}

it('serves images, audio and video inline to the owner', function (string $name, string $mime) {
    $user = User::factory()->create();
    $media = storedMedia($user, $name, $mime);

    $response = $this->actingAs($user)->get($media->url())->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($response->headers->get('Content-Type'))->toStartWith($mime)
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->baseResponse->getFile()->getContent())->toBe('file-bytes');
})->with([
    ['photo.png', 'image/png'],
    ['snap.webp', 'image/webp'],
    ['note.mp3', 'audio/mpeg'],
    ['note.wav', 'audio/wav'],
    ['clip.mp4', 'video/mp4'],
    ['clip.mov', 'video/quicktime'],
]);

/**
 * Seeking in audio and video needs byte ranges, and Safari may refuse to play without them.
 * FilesystemAdapter::response() returns a StreamedResponse, which has no Range handling at
 * all, so inline types must go out as a BinaryFileResponse instead.
 */
it('answers range requests for inline media so audio and video can seek', function () {
    $user = User::factory()->create();
    $media = storedMedia($user, 'clip.mp4', 'video/mp4');

    $full = $this->actingAs($user)->get($media->url())->assertOk();
    expect($full->headers->get('Accept-Ranges'))->toBe('bytes');

    $partial = $this->actingAs($user)->get($media->url(), ['Range' => 'bytes=0-3']);

    expect($partial->getStatusCode())->toBe(206)
        ->and($partial->headers->get('Content-Range'))->toBe('bytes 0-3/10')
        ->and($partial->headers->get('Content-Length'))->toBe('4');
});

it('does not sandbox inline media, because sandbox breaks the browser players', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(storedMedia($user, 'clip.mp4', 'video/mp4')->url())->assertOk();

    expect($response->headers->get('Content-Security-Policy'))->toBeNull();
});

it('forces a download for everything else, including pdf, svg and html', function (string $name, string $mime) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(storedMedia($user, $name, $mime)->url())->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Content-Security-Policy'))->toBe('sandbox');
})->with([
    ['guide.pdf', 'application/pdf'],
    ['drawing.svg', 'image/svg+xml'],
    ['page.html', 'text/html'],
    ['kit.zip', 'application/zip'],
    ['notes.txt', 'text/plain'],
]);

it('downloads with the original file name, not the url-safe one', function () {
    $user = User::factory()->create();
    $media = storedMedia($user, 'My Notes.zip', 'application/zip');

    $response = $this->actingAs($user)->get($media->url())->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('My Notes.zip');
});

it('returns 404 for another user, indistinguishable from a missing file', function () {
    $media = storedMedia(User::factory()->create(), 'photo.png', 'image/png');

    $this->actingAs(User::factory()->create())->get($media->url())->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('media.show', ['media' => 'no-such-uuid', 'name' => 'x.png']))->assertNotFound();
});

it('redirects guests to the login page', function () {
    $media = storedMedia(User::factory()->create(), 'photo.png', 'image/png');

    $this->get($media->url())->assertRedirect('/login');
});

it('returns 404, not a server error, when the row exists but the file is gone', function () {
    $user = User::factory()->create();
    $media = Media::factory()->for($user)->create(['mime_type' => 'image/png']);

    $this->actingAs($user)->get($media->url())->assertNotFound();
});

it('looks the file up by uuid and ignores the name segment', function () {
    $user = User::factory()->create();
    $media = storedMedia($user, 'photo.png', 'image/png');

    $this->actingAs($user)->get(route('media.show', ['media' => $media->uuid, 'name' => 'anything.bin']))->assertOk();
});
