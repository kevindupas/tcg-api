<?php

use App\Http\Controllers\Api\v1\CardController;
use App\Http\Controllers\Api\v1\VersionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('/version', [VersionController::class, 'getLatestVersion']);
    Route::get('/cards', [CardController::class, 'index']);
    Route::get('/cards/updates', [CardController::class, 'updates']);
});
