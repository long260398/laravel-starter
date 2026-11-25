<?php

namespace Tests\Feature;

use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_includes_published_category_url(): void
    {
        $category = PostCategory::factory()->published()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200)
            ->assertSee(route('posts.category-show', $category->slug), false);
    }

    public function test_sitemap_does_not_include_draft_category_url(): void
    {
        $category = PostCategory::factory()->create();

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee(route('posts.category-show', $category->slug), false);
    }
}
