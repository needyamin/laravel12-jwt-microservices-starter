<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;
use App\Services\RouteConfig;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| Routes are automatically registered from the services configuration file.
| To add new routes, edit config/services.php instead of this file.
|
*/

// Health check endpoint (no authentication required)
Route::get('/health', [GatewayController::class, 'health']);

// Register all routes from service configurations
// This automatically creates routes for all services defined in config/services.php
RouteConfig::registerAll();
