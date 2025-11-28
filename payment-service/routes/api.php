<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/health', function () {
    return response()->json([
        'service' => 'payments-service',
        'status' => 'up',
        'timestamp' => now()
    ]);
});

// Protected routes (require authentication)
Route::middleware(['trust.gateway'])->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::put('/{id}/status', [PaymentController::class, 'updateStatus']);
});

