<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MediaFavorite;
use App\Support\VideoLinkParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaFavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->visibleMediaQuery()->with('category');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->string('search')->toString().'%');
        }
        if ($request->filled('platform')) {
            $query->where('platform', $request->input('platform'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->input('sort') === 'terlama') {
            $query->oldest();
        } else {
            $query->latest();
        }

        return view('media-favorites.index', [
            'mediaItems' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'platforms' => $this->visibleMediaQuery()->distinct()->orderBy('platform')->pluck('platform'),
            'parser' => app(VideoLinkParser::class),
        ]);
    }

    public function create(): View
    {
        return view('media-favorites.form', [
            'mediaFavorite' => new MediaFavorite(['is_favorite' => true]),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, VideoLinkParser $parser): RedirectResponse
    {
        $validated = $this->validatedMedia($request, $parser);
        $validated['user_id'] = $request->user()->id;
        $mediaFavorite = MediaFavorite::create($validated);

        return redirect()->route('media-favorites.show', $mediaFavorite)->with('success', 'Media berhasil disimpan.');
    }

    public function show(MediaFavorite $mediaFavorite, VideoLinkParser $parser): View
    {
        $this->assertAccessible($mediaFavorite);

        return view('media-favorites.show', [
            'mediaFavorite' => $mediaFavorite->load('category'),
            'embedUrl' => $parser->embedUrl($mediaFavorite->platform, $mediaFavorite->video_id),
        ]);
    }

    public function edit(MediaFavorite $mediaFavorite): View
    {
        $this->assertAccessible($mediaFavorite);

        return view('media-favorites.form', [
            'mediaFavorite' => $mediaFavorite,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, MediaFavorite $mediaFavorite, VideoLinkParser $parser): RedirectResponse
    {
        $this->assertAccessible($mediaFavorite);
        $mediaFavorite->update($this->validatedMedia($request, $parser));

        return redirect()->route('media-favorites.show', $mediaFavorite)->with('success', 'Media berhasil diperbarui.');
    }

    public function destroy(MediaFavorite $mediaFavorite): RedirectResponse
    {
        $this->assertAccessible($mediaFavorite);
        $mediaFavorite->delete();

        return redirect()->route('media-favorites.index')->with('success', 'Media berhasil dihapus.');
    }

    public function toggleFavorite(MediaFavorite $mediaFavorite): RedirectResponse
    {
        $this->assertAccessible($mediaFavorite);
        $mediaFavorite->update(['is_favorite' => ! $mediaFavorite->is_favorite]);

        return back()->with('success', $mediaFavorite->is_favorite
            ? 'Media ditambahkan ke favorit.'
            : 'Media dihapus dari favorit.');
    }

    private function validatedMedia(Request $request, VideoLinkParser $parser): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'url' => ['required', 'string', 'url', 'max:2048'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_favorite' => ['nullable', 'boolean'],
        ]);
        $platform = $parser->parse($validated['url']);

        return [
            ...$validated,
            ...$platform,
            'is_favorite' => $request->boolean('is_favorite'),
        ];
    }

    private function visibleMediaQuery()
    {
        return MediaFavorite::query()->where(function ($query) {
            $query->whereNull('user_id')->orWhere('user_id', auth()->id());
        });
    }

    private function assertAccessible(MediaFavorite $mediaFavorite): void
    {
        abort_unless($mediaFavorite->user_id === null || $mediaFavorite->user_id === auth()->id(), 404);
    }
}
