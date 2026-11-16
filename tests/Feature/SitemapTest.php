<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_has_xml_content_type(): void
    {
        $this->get('/sitemap.xml')->assertHeader('Content-Type', 'application/xml');
    }

    public function test_sitemap_includes_published_post_and_static_page(): void
    {
        $post = Post::factory()->published()->create();
        $page = StaticPage::factory()->published()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertSee(route('posts.show', $post->slug), false);
        $response->assertSee(route('static-pages.show', $page->slug), false);
    }

    public function test_sitemap_excludes_draft_and_future_posts_and_draft_static_pages(): void
    {
        $draftPost = Post::factory()->create();
        $futurePost = Post::factory()->published()->create(['published_at' => now()->addDay()]);
        $draftPage = StaticPage::factory()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee(route('posts.show', $draftPost->slug), false);
        $response->assertDontSee(route('posts.show', $futurePost->slug), false);
        $response->assertDontSee(route('static-pages.show', $draftPage->slug), false);
    }
}
