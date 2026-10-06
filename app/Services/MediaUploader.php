<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploader
{
    /** Store the bytes under a random uuid on the private disk and record them for the user. */
    public function store(User $user, UploadedFile $file): Media
    {
        $uuid = (string) Str::uuid();
        // Read both before putFileAs(), which moves the temporary file away.
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $size = (int) $file->getSize();

        Storage::disk(Media::DISK)->putFileAs('media', $file, $uuid);

        return $user->media()->create([
            'uuid' => $uuid,
            'original_name' => $this->displayName($file->getClientOriginalName()),
            'mime_type' => $mime,
            'size' => $size,
        ]);
    }

    private function displayName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?? '');

        return Str::limit($name !== '' ? $name : 'file', 255, '');
    }
}
