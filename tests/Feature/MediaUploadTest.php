<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(Media::DISK);
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
