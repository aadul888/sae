<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFallbackController extends Controller
{
    /**
     * Fallback untuk menyajikan berkas dari storage/app/public bila symlink
     * public/storage tidak tersedia, rusak, atau dilarang oleh hosting server.
     * Bila symlink aktif, web server (Nginx/Apache) langsung menyajikan berkas fisik
     * tanpa melewati PHP.
     */
    public function show(string $path): BinaryFileResponse
    {
        $cleanPath = ltrim(str_replace(['../', '..\\'], '', $path), '/\\');
        if ($cleanPath === '' || str_contains($cleanPath, '..')) {
            abort(404);
        }

        $fullPath = storage_path('app/public/' . $cleanPath);
        $realStorageRoot = realpath(storage_path('app/public'));
        $realFile = realpath($fullPath);

        if (!$realFile || !$realStorageRoot || !str_starts_with($realFile, $realStorageRoot) || !is_file($realFile)) {
            abort(404);
        }

        $filename = basename($realFile);
        if (str_starts_with($filename, '.') || preg_match('/\.(php[0-9]?|phtml|phar|htaccess|env)$/i', $filename)) {
            abort(404);
        }

        return response()->file($realFile, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
