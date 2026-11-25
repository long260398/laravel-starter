<?php

namespace App\Http\Controllers;

use App\Actions\BuildPostJsonLd;
use App\Actions\BuildPostTableOfContents;
use App\Actions\GetRelatedPosts;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(): View
    {
        return view('posts.index', [
            'posts' => Post::query()->published()->latest('published_at')->paginate(12),
        ]);
    }

    public function category(string $slug): View
    {
        $category = PostCategory::query()->published()->where('slug', $slug)->firstOrFail();

        return view('posts.category', [
            'category' => $category,
            'posts' => Post::query()
                ->published()
                ->where('category_id', $category->id)
                ->latest('published_at')
                ->paginate(12),
        ]);
    }

    public function tag(string $slug): View
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();

        $posts = Post::query()
            ->published()
            ->whereHas('tags', fn ($query) => $query->where('tags.id', $tag->id))
            ->latest('published_at')
            ->paginate(12);

        return view('posts.tag', [
            'tag' => $tag,
            'posts' => $posts,
            'noindex' => $posts->total() < 3,
        ]);
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $posts = null;

        if (mb_strlen($query) >= 2) {
            // Dùng '!' làm ký tự escape, không dùng '\' — MySQL (kết nối PDO mặc
            // định bật emulated prepares) tự nhân đôi backslash khi quote giá
            // trị bind, làm lệch số lượng '\' trong pattern LIKE so với SQLite
            // (chạy đúng trên SQLite nhưng sai trên MySQL). '!' không có nghĩa
            // đặc biệt ở lớp quote của cả 2 driver nên portable giữa 2 bên.
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
            $like = "%{$escaped}%";

            $posts = Post::query()
                ->published()
                ->where(fn ($q) => $q->whereRaw('title like ? escape ?', [$like, '!'])->orWhereRaw('excerpt like ? escape ?', [$like, '!']))
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString();
        }

        return view('posts.search', [
            'query' => $query,
            'posts' => $posts,
            'noindex' => true,
        ]);
    }

    public function show(string $slug, BuildPostTableOfContents $buildToc, GetRelatedPosts $getRelatedPosts, BuildPostJsonLd $buildJsonLd): View
    {
        $post = Post::query()->published()->with(['category', 'tags'])->where('slug', $slug)->firstOrFail();

        $toc = $buildToc->handle($post->safe_content);
        $jsonLd = $buildJsonLd->handle($post);

        return view('posts.show', [
            'post' => $post,
            'tocHtml' => $toc['html'],
            'tocItems' => $toc['items'],
            'relatedPosts' => $getRelatedPosts->handle($post),
            'articleJsonLd' => $jsonLd['article'],
            'breadcrumbJsonLd' => $jsonLd['breadcrumb'],
        ]);
    }
}
