<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_page_returns_200_and_lists_only_published_posts_in_category(): void
    {
        $category = PostCategory::factory()->published()->create();
        $otherCategory = PostCategory::factory()->published()->create();

        $published = Post::factory()->published()->for($category, 'category')->create();
        $draft = Post::factory()->for($category, 'category')->create();
        $otherCategoryPost = Post::factory()->published()->for($otherCategory, 'category')->create();

        $response = $this->get("/blog/category/{$category->slug}");

        $response->assertStatus(200)
            ->assertSee($category->name)
            ->assertSee($published->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($otherCategoryPost->title);
    }

    public function test_category_page_returns_404_for_draft_category(): void
    {
        $category = PostCategory::factory()->create();

        $this->get("/blog/category/{$category->slug}")->assertStatus(404);
    }

    public function test_category_page_returns_404_for_unknown_slug(): void
    {
        $this->get('/blog/category/khong-ton-tai')->assertStatus(404);
    }
}
