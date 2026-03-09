<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves private files from storage/app/private after authentication.
 * All file downloads must go through this controller — never via public symlinks.
 */
class FileAccessController extends Controller
{
    /**
     * Stream a private file to the authenticated user.
     *
     * @param  string  $path  Relative path within the private disk (e.g. "articles/photo.jpg")
     */
    public function show(Request $request, string $path): StreamedResponse
    {
        abort_unless($request->user() !== null, 403);

        // Prevent path traversal attacks
        $path = ltrim($path, '/');
        abort_if(str_contains($path, '..'), 400);

        abort_unless(Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->download($path);
    }
}
