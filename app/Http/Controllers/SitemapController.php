<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\StaticPage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function (): string {
            $urls = collect([
                ['loc' => url('/'), 'lastmod' => now()],
            ])
                ->merge(
                    Post::query()->published()->get()->map(fn (Post $post): array => [
                        'loc' => route('posts.show', $post->slug),
                        'lastmod' => $post->updated_at,
                    ]),
                )
                ->merge(
                    PostCategory::query()->published()->get()->map(fn (PostCategory $category): array => [
                        'loc' => route('posts.category-show', $category->slug),
                        'lastmod' => $category->updated_at,
                    ]),
                )
                ->merge(
                    StaticPage::query()->published()->get()->map(fn (StaticPage $page): array => [
                        'loc' => route('static-pages.show', $page->slug),
                        'lastmod' => $page->updated_at,
                    ]),
                )
                ->merge(module_registry()->sitemapUrls());

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
