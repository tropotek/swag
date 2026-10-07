<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Media;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared by the session and token download endpoints, which differ only in their middleware.
 * The name segment is cosmetic — lookup is by uuid — so the token route leaves it optional and
 * an agent can fetch a file from its uuid alone.
 */
trait ServesMedia
{
    public function show(Media $media, ?string $name = null): Response
    {
        Gate::authorize('view', $media);

        $disk = Storage::disk(Media::DISK);
        abort_unless($disk->exists($media->path()), 404);

        // Inline types go out as a BinaryFileResponse: it answers Range requests, which audio
        // and video need to seek. They also skip the sandbox CSP, which would break the
        // browser's media players; the inline list is a closed set of non-executable types
        // and the MIME came from the file's content, so nosniff is enough there.
        if ($media->isInline()) {
            return response()
                ->file($disk->path($media->path()), [
                    'Content-Type' => $media->mime_type,
                    'X-Content-Type-Options' => 'nosniff',
                ])
                ->setContentDisposition('inline', $media->original_name, $media->urlName());
        }

        return $disk->response($media->path(), $media->original_name, [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
        ], 'attachment');
    }
}
