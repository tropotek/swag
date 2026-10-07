<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\PageController;
use Illuminate\Support\Facades\Route;

Route::get('openapi.json', OpenApiController::class)->middleware('throttle:api')->name('api.openapi');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('pages', PageController::class)->names('api.pages');
});

// Uploads get a looser limit of their own: a page can carry many images, and one request per
// image would otherwise eat the budget shared with page writes.
Route::middleware(['auth:sanctum', 'throttle:media'])->group(function () {
    Route::post('media', [MediaController::class, 'store'])->name('api.media.store');
    Route::get('media/{media}/{name?}', [MediaController::class, 'show'])->name('api.media.show');
});
