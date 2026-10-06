<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaRequest;
use App\Http\Resources\MediaResource;
use App\Services\MediaUploader;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function store(MediaRequest $request, MediaUploader $uploader): JsonResponse
    {
        $media = $uploader->store($request->user(), $request->file('file'));

        return MediaResource::make($media)->response()->setStatusCode(201);
    }
}
