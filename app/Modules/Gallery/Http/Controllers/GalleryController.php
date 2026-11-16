<?php

namespace App\Modules\Gallery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Models\GalleryAlbum;
use Illuminate\Contracts\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        $albums = GalleryAlbum::query()->published()->orderBy('sort_order')->get();

        return view('gallery::index', [
            'albums' => $albums,
        ]);
    }

    public function show(string $slug): View
    {
        $album = GalleryAlbum::query()->published()->where('slug', $slug)->firstOrFail();

        $album->load('images');

        return view('gallery::show', [
            'album' => $album,
        ]);
    }
}
