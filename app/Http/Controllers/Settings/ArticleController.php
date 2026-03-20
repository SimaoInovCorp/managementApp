<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\VatRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the article / product catalogue (Settings section).
 *
 * Photos are stored on the private disk under `articles/` so they are
 * only accessible through the authenticated FileAccessController route.
 */
class ArticleController extends Controller
{
    /**
     * List all articles with their VAT rates eagerly loaded.
     */
    public function index(Request $request): Response
    {
        $articles = Article::with('vatRate')
            ->orderBy('reference')
            ->get();

        return Inertia::render('settings/Articles', [
            'articles' => ArticleResource::collection($articles),
            'vatRates' => VatRate::orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    /**
     * Store a new article, optionally uploading a photo.
     */
    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')
                ->store('articles', 'private');
        }

        // Remove the 'photo' key — not a real column
        unset($data['photo']);

        Article::create($data);

        return back()->with('success', 'Article created successfully.');
    }

    /**
     * Update an existing article, replacing the photo when a new one is provided.
     */
    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            // Delete the old photo if it exists
            if ($article->photo_path) {
                Storage::disk('private')->delete($article->photo_path);
            }
            $data['photo_path'] = $request->file('photo')
                ->store('articles', 'private');
        }

        unset($data['photo']);

        $article->update($data);

        return back()->with('success', 'Article updated successfully.');
    }

    /**
     * Delete an article, removing its photo from storage.
     */
    public function destroy(Article $article): RedirectResponse
    {
        if ($article->photo_path) {
            Storage::disk('private')->delete($article->photo_path);
        }

        $article->delete();

        return back()->with('success', 'Article deleted.');
    }
}
