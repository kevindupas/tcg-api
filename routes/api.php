<?php

use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\ExtensionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('/cards', [CardController::class, 'index']);
    Route::get('/cards/updates', [CardController::class, 'updates']);
    Route::get('/version', [CardController::class, 'metadata']);
});
