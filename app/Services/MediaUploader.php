<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaUploader
{
    /** Store the bytes under a random uuid on the private disk and record them for the user. */
    public function store(User $user, UploadedFile $file): Media
    {
        $uuid = (string) Str::uuid();
        // Read both before putFileAs(), which moves the temporary file away.
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $size = (int) $file->getSize();

        $disk = Storage::disk(Media::DISK);

        // The disk is configured with throw => false, so a failed write returns false and logs
        // nothing. Without this check an unwritable storage directory would return 201 with a
        // markdown link to bytes that were never stored.
        if ($disk->putFileAs('media', $file, $uuid) === false) {
            throw new RuntimeException('Could not write the uploaded file to the '.Media::DISK.' disk.');
        }

        try {
            return $user->media()->create([
                'uuid' => $uuid,
                'original_name' => $this->displayName($file->getClientOriginalName()),
                'mime_type' => $mime,
                'size' => $size,
            ]);
        } catch (Throwable $e) {
            // The bytes are already on disk and nothing will ever reference them now.
            $disk->delete('media/'.$uuid);

            throw $e;
        }
    }

    private function displayName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?? '');

        return Str::limit($name !== '' ? $name : 'file', 255, '');
    }
}
