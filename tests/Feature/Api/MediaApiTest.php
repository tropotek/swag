<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake(Media::DISK);
});

it('requires a token', function () {
    $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('a.txt', 1)])->assertUnauthorized();
});

it('stores an upload for the token user and returns the markdown to embed', function () {
    $user = Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('Holiday Photo.png', 10, 'image/png')])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'url', 'name', 'mime_type', 'size', 'markdown']])
        ->assertJsonPath('data.name', 'Holiday Photo.png')
        ->assertJsonPath('data.mime_type', 'image/png');

    $media = Media::sole();
    expect($media->user_id)->toBe($user->id)
        ->and($response->json('data.url'))->toBe($media->url())
        ->and($response->json('data.markdown'))->toBe('!['.'Holiday Photo.png]('.$media->url().')');
    Storage::disk(Media::DISK)->assertExists('media/'.$media->uuid);
});

it('returns link markdown for non-images', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('kit.zip', 5, 'application/zip')])
        ->assertCreated()
        ->assertJsonPath('data.markdown', '[kit.zip]('.Media::sole()->url().')');
});

/**
 * The faked uploads above can't prove this: Illuminate\Http\Testing\File::getMimeType()
 * returns `mimeTypeToReport ?: MimeType::from($this->name)`, which is name-based, while a
 * real upload is sniffed from content. These two use a real UploadedFile so the detection
 * the app actually relies on is exercised, including a lying file extension.
 */
function realUpload(string $name, string $bytes): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'swag-media-');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, null, null, true);
}

it('detects the mime type from the file content, not its name', function () {
    Sanctum::actingAs(User::factory()->create());
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');

    $this->postJson('/api/media', ['file' => realUpload('screenshot.png', $png)])
        ->assertCreated()
        ->assertJsonPath('data.mime_type', 'image/png');

    expect(Media::sole()->isImage())->toBeTrue();
});

it('does not trust an image extension on a file that is really html', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/media', ['file' => realUpload('trap.png', '<html><body><script>alert(1)</script></body></html>')])
        ->assertCreated();

    $media = Media::sole();
    expect($media->mime_type)->toBe('text/html')
        ->and($media->isInline())->toBeFalse()
        ->and($media->isImage())->toBeFalse()
        ->and($response->json('data.markdown'))->toStartWith('[');
});

it('renders the returned markdown as a working image even for awkward names', function () {
    Sanctum::actingAs(User::factory()->create());

    $markdown = $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('my photo [1] (2).png', 5, 'image/png')])
        ->assertCreated()->json('data.markdown');

    $html = Page::factory()->make(['body_markdown' => $markdown])->renderedBody();

    expect($html)->toContain('<img src="'.Media::sole()->url().'"');
});

it('rejects blocked extensions with the zip hint', function (string $name) {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/media', ['file' => UploadedFile::fake()->create($name, 1)])
        ->assertStatus(422)
        ->assertJsonPath('errors.file.0', "This file type isn't allowed. Put it in a zip file and upload that.");

    expect(Media::count())->toBe(0);
})->with(['setup.exe', 'SETUP.EXE', 'a.exe.txt', 'run.sh', 'index.php', 'macro.bat']);

it('accepts a zip and ordinary documents', function (string $name) {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/media', ['file' => UploadedFile::fake()->create($name, 1)])->assertCreated();
})->with(['kit.zip', 'notes.txt', 'report.pdf', 'drawing.svg', 'page.html']);

it('rejects a file over the size limit and names the limit', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('big.bin', 25601)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');

    expect($response->json('errors.file.0'))->toContain('25 MB');
});

it('states a sub-megabyte limit in kilobytes rather than rounding it to 0 MB', function () {
    Sanctum::actingAs(User::factory()->create());
    config(['swag.media.max_kb' => 100]);

    $response = $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('big.bin', 101)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');

    expect($response->json('errors.file.0'))->toContain('100 KB')->not->toContain('0 MB');

    $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('ok.bin', 100)])->assertCreated();
});

it('rejects a request with no file, saying the server limit may be the cause', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/media', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');

    expect($this->postJson('/api/media', [])->json('errors.file.0'))->toContain('25 MB');
});

it('rejects a file that PHP failed to receive, saying the server limit may be the cause', function () {
    Sanctum::actingAs(User::factory()->create());
    $broken = new UploadedFile(__FILE__, 'big.bin', null, UPLOAD_ERR_INI_SIZE, true);

    $response = $this->postJson('/api/media', ['file' => $broken])->assertStatus(422)->assertJsonValidationErrors('file');

    expect($response->json('errors.file.0'))->toContain('25 MB');
});

it('rejects a non-file value', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/media', ['file' => 'not a file'])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('keeps a hostile original name out of the path and the display name', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/media', ['file' => UploadedFile::fake()->create('../../etc/pass wd.txt', 1)])->assertCreated();

    $media = Media::sole();
    expect($media->original_name)->toBe('pass wd.txt')
        ->and($media->original_name)->not->toContain('/')
        ->and($media->path())->toBe('media/'.$media->uuid);
    Storage::disk(Media::DISK)->assertExists('media/'.$media->uuid);
});

it('returns a JSON 422 even without an Accept header', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->post('/api/media', [])->assertStatus(422)->assertJsonValidationErrors('file');
});
