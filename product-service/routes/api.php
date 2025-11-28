<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/health', function () {
    return response()->json([
        'service' => 'products-service',
        'status' => 'up',
        'timestamp' => now()
    ]);
});

// Public product routes
Route::get('/', [ProductController::class, 'index']);
Route::get('/{id}', [ProductController::class, 'show']);

// Protected routes (require authentication)
Route::middleware(['trust.gateway'])->group(function () {
    Route::post('/', [ProductController::class, 'store']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});

