<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        return view('posts.index', [
            'posts' => Post::query()->published()->latest('published_at')->paginate(12),
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();

        return view('posts.show', [
            'post' => $post,
        ]);
    }
}
