<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDigitalArchiveRequest;
use App\Http\Resources\DigitalArchiveResource;
use App\Models\DigitalArchive;
use App\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manages the digital archive: upload, list, download, and delete private files.
 *
 * All files are stored under storage/app/private/archive/ and served
 * through this authenticated controller — never via public symlinks.
 */
class DigitalArchiveController extends Controller
{
    /**
     * List all archived files with optional category / entity filters.
     */
    public function index(): Response
    {
        $archives = DigitalArchive::with([
            'entity:id,name',
            'uploader:id,name',
        ])
            ->orderByDesc('created_at')
            ->paginate(15);

        $entities = Entity::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('archive/Index', [
            'archives' => DigitalArchiveResource::collection($archives),
            'entities' => $entities,
        ]);
    }

    /**
     * Upload a new file and create the archive record.
     */
    public function store(StoreDigitalArchiveRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        // Build a collision-proof path: archive/{uuid}.{ext}
        $path = 'archive/'.Str::uuid().'.'.$file->getClientOriginalExtension();

        Storage::disk('private')->putFileAs(
            '',     // root of private disk since path includes subdir
            $file,
            $path,
        );

        DigitalArchive::create([
            'name' => $request->validated()['name'],
            'path' => $path,
            'category' => $request->validated()['category'] ?? null,
            'entity_id' => $request->validated()['entity_id'] ?? null,
            'description' => $request->validated()['description'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    /**
     * Stream the private file to the authenticated user.
     * The original `name` field is used as the download filename.
     */
    public function show(DigitalArchive $archive): StreamedResponse
    {
        abort_unless(Storage::disk('private')->exists($archive->path), 404);

        // Append the original extension to the friendly display name
        $ext = pathinfo($archive->path, PATHINFO_EXTENSION);
        $downloadName = $archive->name.($ext ? ".{$ext}" : '');

        return Storage::disk('private')->download($archive->path, $downloadName);
    }

    /**
     * Delete the archive record and its associated file from storage.
     */
    public function destroy(DigitalArchive $archive): RedirectResponse
    {
        Storage::disk('private')->delete($archive->path);
        $archive->delete();

        return back()->with('success', 'File deleted.');
    }
}
