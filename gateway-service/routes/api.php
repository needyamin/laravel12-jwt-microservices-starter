<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

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

// Health check endpoint (no authentication required)
Route::get('/health', [GatewayController::class, 'health']);

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/login', function (Request $request) {
        return app(GatewayController::class)->route($request, 'users', 'api/auth/login');
    });
    
    Route::post('/register', function (Request $request) {
        return app(GatewayController::class)->route($request, 'users', 'api/auth/register');
    });
    
    Route::post('/refresh', function (Request $request) {
        return app(GatewayController::class)->route($request, 'users', 'api/auth/refresh');
    });
    
    Route::post('/logout', function (Request $request) {
        return app(GatewayController::class)->route($request, 'users', 'api/auth/logout');
    });
});

// Protected routes (require authentication)
Route::middleware(['gateway.auth'])->group(function () {
    
    // User profile routes
    Route::prefix('users')->group(function () {
        Route::get('/profile', function (Request $request) {
            return app(GatewayController::class)->route($request, 'users', 'api/profile');
        });
        
        Route::put('/profile', function (Request $request) {
            return app(GatewayController::class)->route($request, 'users', 'api/profile');
        });
    });
    
    // Order management routes
    Route::prefix('orders')->group(function () {
        Route::get('/', function (Request $request) {
            return app(GatewayController::class)->route($request, 'orders', 'api/');
        });
        
        Route::post('/', function (Request $request) {
            return app(GatewayController::class)->route($request, 'orders', 'api/');
        });
        
        Route::get('/{id}', function (Request $request, $id) {
            return app(GatewayController::class)->route($request, 'orders', "api/{$id}");
        });
        
        Route::put('/{id}', function (Request $request, $id) {
            return app(GatewayController::class)->route($request, 'orders', "api/{$id}");
        });
        
        Route::delete('/{id}', function (Request $request, $id) {
            return app(GatewayController::class)->route($request, 'orders', "api/{$id}");
        });
    });
});

// Admin only routes (separate group)
Route::middleware(['gateway.auth', 'require.role:admin,superadmin'])->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', function (Request $request) {
            return app(GatewayController::class)->route($request, 'users', 'api/');
        });
        
        Route::get('/{id}', function (Request $request, $id) {
            return app(GatewayController::class)->route($request, 'users', "api/{$id}");
        });
        
        Route::put('/{id}', function (Request $request, $id) {
            return app(GatewayController::class)->route($request, 'users', "api/{$id}");
        });
        
        Route::delete('/{id}', function (Request $request, $id) {
            return app(GatewayController::class)->route($request, 'users', "api/{$id}");
        });
    });
});

// Moderator and Admin routes for orders
Route::middleware(['gateway.auth', 'require.role:moderator,admin,superadmin'])->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/admin/all', function (Request $request) {
            return app(GatewayController::class)->route($request, 'orders', 'api/admin/all');
        });
        
        Route::put('/{id}/status', function (Request $request, $id) {
            return app(GatewayController::class)->route($request, 'orders', "api/{$id}/status");
        });
    });
});

// Catch-all route for any unmatched API routes
Route::any('{service}/{path?}', [GatewayController::class, 'route'])
    ->where('service', 'users|orders')
    ->where('path', '.*');
