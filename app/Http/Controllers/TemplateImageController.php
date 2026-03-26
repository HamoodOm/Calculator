<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateImageController extends Controller
{
    /**
     * Serve a template background image with authentication check.
     *
     * This secures template images that would otherwise be served directly
     * from public/images/templates/ without any authentication.
     *
     * Route: GET /secure/template-image/{path}
     */
    public function serve(Request $request, string $path)
    {
        // Prevent path traversal attacks
        $path = ltrim($path, '/');
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(400, 'Invalid path');
        }

        // Only serve files under images/templates/
        $absolutePath = public_path('images/templates/' . $path);

        if (!file_exists($absolutePath) || !is_file($absolutePath)) {
            abort(404, 'Template image not found');
        }

        // Determine MIME type
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
        ];

        if (!isset($mimeTypes[$extension])) {
            abort(403, 'File type not allowed');
        }

        $mimeType = $mimeTypes[$extension];

        // Cache the response for performance (browser-side cache, 1 hour)
        return response()->file($absolutePath, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
