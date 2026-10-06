<?php

namespace App\Http\Controllers;

use App\Http\Requests\MediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaUploader;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function store(MediaRequest $request, MediaUploader $uploader): JsonResponse
    {
        $media = $uploader->store($request->user(), $request->file('file'));

        return MediaResource::make($media)->response()->setStatusCode(201);
    }

    public function show(Media $media, string $name): never
    {
        abort(404); // replaced in Task 3
    }
}
