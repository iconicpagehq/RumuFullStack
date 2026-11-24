<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Serve image from storage
     * Alternative to storage symlink (for servers where symlink doesn't work)
     */
    public function serve($path)
    {
        $fullPath = 'avatars/' . $path;

        if (!Storage::disk('public')->exists($fullPath)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($fullPath);
        $type = Storage::disk('public')->mimeType($fullPath);

        return response($file, 200)
            ->header('Content-Type', $type)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
