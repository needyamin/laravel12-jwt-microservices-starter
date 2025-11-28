<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

Route::get('/health', function () {
    return response()->json([
        'service' => 'carts-service',
        'status' => 'up',
        'timestamp' => now()
    ]);
});

// Protected routes (require authentication)
Route::middleware(['trust.gateway'])->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/', [CartController::class, 'clear']);
});

