<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/files/{path}', function ($path) {
//     $path = str_replace(['../', '..\\'], '', $path);
//     $file = storage_path('app/public/' . $path);
    
//     if (!file_exists($file) || !is_file($file)) {
//         abort(404);
//     }
    
//     return response()->file($file);
// })->where('path', '.*');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});