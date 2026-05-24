<?php

use App\Http\Controllers\Api\RefereeApiController;
use Illuminate\Support\Facades\Route;

Route::post('/referee/login', [RefereeApiController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('referee')->group(function () {
    Route::get('/me', [RefereeApiController::class, 'me']);
    Route::get('/queue', [RefereeApiController::class, 'queue']);
    Route::get('/matches/{match}', [RefereeApiController::class, 'show']);
    Route::post('/matches/{match}/start', [RefereeApiController::class, 'start']);
    Route::post('/matches/{match}/frame', [RefereeApiController::class, 'frame']);
    Route::post('/matches/{match}/end', [RefereeApiController::class, 'end']);
    Route::post('/matches/{match}/sign', [RefereeApiController::class, 'sign']);
});
