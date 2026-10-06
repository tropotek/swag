<?php

use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\PageController;
use Illuminate\Support\Facades\Route;

Route::get('openapi.json', OpenApiController::class)->middleware('throttle:api')->name('api.openapi');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('pages', PageController::class)->names('api.pages');
});
