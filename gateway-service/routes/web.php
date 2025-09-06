<?php

use Illuminate\Support\Facades\Route;

// Only handle non-API routes
Route::get('/', function () {
    return view('welcome');
});

