<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FlareController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

// Handle OPTIONS requests for all routes (CORS preflight)
Route::options('{any}', function () {
    return response('', 200);
})->where('any', '.*');

// Authentication routes with strict rate limiting (prevent brute force)
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public routes with generous rate limiting (users browse maps frequently)
Route::prefix('flares')->middleware('throttle:100,1')->group(function () {
    Route::get('/', [FlareController::class, 'index']); // Get all flares for map
    Route::get('/{flare}', [FlareController::class, 'show']); // Get specific flare details
    Route::get('/nearby/known-places', [FlareController::class, 'nearbyKnownPlaces']); // Get nearby known places
});

// Protected routes - require authentication via Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes (moderate rate limiting)
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);
    });
    
    // Flare management with moderate rate limiting (prevent spam)
    Route::prefix('flares')->middleware('throttle:10,1')->group(function () {
        Route::post('/', [FlareController::class, 'store']); // Create new flare
        Route::put('/{flare}', [FlareController::class, 'update']); // Update own flare
        Route::delete('/{flare}', [FlareController::class, 'destroy']); // Delete own flare
        Route::post('/{flare}/images', [FlareController::class, 'uploadImage']); // Add image to flare
        Route::post('/{flare}/contribute', [FlareController::class, 'contribute']); // Add to existing flare
    });
    
    // User profile routes with generous rate limiting
    Route::prefix('user')->middleware('throttle:30,1')->group(function () {
        Route::get('/', [UserController::class, 'profile']); // Get current user profile
        Route::put('/profile', [UserController::class, 'updateProfile']); // Update profile (name, username)
        Route::post('/profile-photo', [UserController::class, 'updateProfilePhoto']); // Update profile photo
        Route::delete('/profile-photo', [UserController::class, 'deleteProfilePhoto']); // Delete profile photo
        Route::get('/flares', [UserController::class, 'userFlares']); // Get user's flares
        Route::get('/stats', [UserController::class, 'userStats']); // Get user statistics
    });
    
    // Legacy user route (keep for compatibility)
    Route::get('/user', [UserController::class, 'profile'])->middleware('throttle:30,1');
});