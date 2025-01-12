<?php

use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\ExtensionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::get('extensions', [ExtensionController::class, 'index']);
// Route::get('extensions/{extension}', [ExtensionController::class, 'show']);
// Route::get('extensions/{extension}/cartes', [ExtensionController::class, 'cartes']);
// Route::get('extensions/{extension}/search', [ExtensionController::class, 'search']);


Route::prefix('v1')->group(function () {
    Route::get('/cards', [CardController::class, 'index']);
    Route::get('/cards/{card}', [CardController::class, 'show']);
});
