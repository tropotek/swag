<?php

use App\Http\Controllers\Api\PageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('pages', PageController::class)->names('api.pages');
});
