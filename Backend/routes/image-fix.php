<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

/**
 * Image Serving Routes
 * Use this if storage symlink doesn't work on your server
 */

// Route to serve avatar images
Route::get('/image/avatars/{filename}', [ImageController::class, 'serve'])
    ->name('image.avatar')
    ->where('filename', '.*');
