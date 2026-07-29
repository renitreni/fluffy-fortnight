<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LinkController;

Route::prefix('v1')->middleware(['auth.api', 'throttle:api_key'])->group(function () {
    Route::apiResource('links', LinkController::class);
});
