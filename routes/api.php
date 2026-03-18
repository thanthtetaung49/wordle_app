<?php

use App\Http\Controllers\WorldeApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('check.api.key')->group(function () {
    Route::post('/set/wordle/answer', [WorldeApiController::class, 'setWordle']);
    Route::get('/player/activity/tracker', [WorldeApiController::class, 'playerTracker']);
});
