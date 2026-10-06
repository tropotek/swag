<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Media::DISK);
});

/** Old enough to be past the command's safety window. */
function orphan(string $name = 'spare.jpg'): Media
{
    $media = Media::factory()->create(['original_name' => $name, 'created_at' => now()->subWeek()]);
    Storage::disk(Media::DISK)->put($media->path(), 'bytes');

    return $media;
}

function referenced(): Media
{
    $media = Media::factory()->create(['original_name' => 'used.jpg', 'created_at' => now()->subWeek()]);
    Storage::disk(Media::DISK)->put($media->path(), 'bytes');
    Page::factory()->for($media->user)->create(['body_markdown' => "![used]({$media->url()})"]);

    return $media;
}

it('lists orphans and deletes nothing by default', function () {
    $media = orphan();

    $this->artisan('swag:prune-media')
        ->expectsOutputToContain('spare.jpg')
        ->expectsOutputToContain('Nothing deleted')
        ->assertSuccessful();

    expect(Media::whereKey($media->id)->exists())->toBeTrue();
    Storage::disk(Media::DISK)->assertExists($media->path());
});

it('deletes the row and the file with --force', function () {
    $media = orphan();

    $this->artisan('swag:prune-media --force')->assertSuccessful();

    expect(Media::whereKey($media->id)->exists())->toBeFalse();
    Storage::disk(Media::DISK)->assertMissing($media->path());
});

it('never touches media a page still references', function () {
    $media = referenced();

    $this->artisan('swag:prune-media --force')->assertSuccessful();

    expect(Media::whereKey($media->id)->exists())->toBeTrue();
    Storage::disk(Media::DISK)->assertExists($media->path());
});

/**
 * The editor uploads the file the moment it is attached, but the page carrying the markdown
 * is not saved until the user presses Save. Pruning inside that window would delete a file
 * the user is in the middle of using.
 */
it('spares media younger than the safety window', function () {
    $fresh = Media::factory()->create(['created_at' => now()->subMinutes(5)]);
    Storage::disk(Media::DISK)->put($fresh->path(), 'bytes');

    $this->artisan('swag:prune-media --force')->assertSuccessful();

    expect(Media::whereKey($fresh->id)->exists())->toBeTrue();
    Storage::disk(Media::DISK)->assertExists($fresh->path());
});

it('honours a custom window', function () {
    $media = Media::factory()->create(['created_at' => now()->subHours(3)]);
    Storage::disk(Media::DISK)->put($media->path(), 'bytes');

    $this->artisan('swag:prune-media --force --hours=2')->assertSuccessful();

    expect(Media::whereKey($media->id)->exists())->toBeFalse();
});

it('deletes a stray file that has no media row at all', function () {
    Storage::disk(Media::DISK)->put('media/not-a-known-uuid', 'bytes');
    touch(Storage::disk(Media::DISK)->path('media/not-a-known-uuid'), now()->subWeek()->timestamp);

    $this->artisan('swag:prune-media --force')
        ->expectsOutputToContain('not-a-known-uuid')
        ->assertSuccessful();

    Storage::disk(Media::DISK)->assertMissing('media/not-a-known-uuid');
});

it('spares a stray file that is still within the window', function () {
    Storage::disk(Media::DISK)->put('media/fresh-stray', 'bytes');

    $this->artisan('swag:prune-media --force')->assertSuccessful();

    Storage::disk(Media::DISK)->assertExists('media/fresh-stray');
});

it('reports when there is nothing to prune', function () {
    referenced();

    $this->artisan('swag:prune-media')
        ->expectsOutputToContain('Nothing to prune')
        ->assertSuccessful();
});
