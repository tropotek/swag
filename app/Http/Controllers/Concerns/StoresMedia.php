<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\MediaRequest;
use App\Http\Resources\MediaResource;
use App\Services\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Shared by the token and session upload endpoints, which differ only in their middleware. */
trait StoresMedia
{
    public function store(MediaRequest $request, MediaUploader $uploader): JsonResponse
    {
        try {
            $media = $uploader->store($request->user(), $request->file('file'));
        } catch (Throwable $e) {
            // Never let the underlying SQL or filesystem error reach the client: with APP_DEBUG
            // on, the framework would hand the editor the raw message to print. The detail
            // belongs in the log.
            Log::error('Media upload failed.', ['exception' => $e, 'user_id' => $request->user()->id]);

            $message = "Couldn't save the file. Please try again.";

            return response()->json(['message' => $message, 'errors' => ['file' => [$message]]], 500);
        }

        return MediaResource::make($media)->response()->setStatusCode(201);
    }
}
