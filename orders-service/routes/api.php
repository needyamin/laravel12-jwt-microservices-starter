<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'service' => 'orders-service',
        'status' => 'up',
        'timestamp' => now()
    ]);
});

// Protected routes (require authentication)
Route::middleware(['trust.gateway'])->group(function () {
    
    // Order management routes
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::put('/{id}', [OrderController::class, 'update']);
    Route::delete('/{id}', [OrderController::class, 'destroy']);
    
    // Moderator and Admin routes
    Route::middleware(['require.role:moderator,admin,superadmin'])->group(function () {
        Route::get('/admin/all', [OrderController::class, 'adminAll']);
        Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
    });
});
