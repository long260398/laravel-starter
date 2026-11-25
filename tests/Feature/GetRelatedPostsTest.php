<?php

namespace Tests\Feature;

use App\Actions\GetRelatedPosts;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetRelatedPostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_published_posts_in_same_category(): void
    {
        $category = PostCategory::factory()->published()->create();
        $post = Post::factory()->published()->for($category, 'category')->create();
        $related = Post::factory()->published()->for($category, 'category')->create();
        $draftInCategory = Post::factory()->for($category, 'category')->create();
        $otherCategoryPost = Post::factory()->published()->create();

        $results = (new GetRelatedPosts)->handle($post);

        $this->assertTrue($results->contains($related));
        $this->assertFalse($results->contains($draftInCategory));
        $this->assertFalse($results->contains($otherCategoryPost));
    }

    public function test_returns_published_posts_sharing_a_tag(): void
    {
        $tag = Tag::factory()->create();

        $post = Post::factory()->published()->create();
        $post->tags()->attach($tag);

        $related = Post::factory()->published()->create();
        $related->tags()->attach($tag);

        $unrelated = Post::factory()->published()->create();

        $results = (new GetRelatedPosts)->handle($post);

        $this->assertTrue($results->contains($related));
        $this->assertFalse($results->contains($unrelated));
    }

    public function test_excludes_current_post(): void
    {
        $category = PostCategory::factory()->published()->create();
        $post = Post::factory()->published()->for($category, 'category')->create();

        $results = (new GetRelatedPosts)->handle($post);

        $this->assertFalse($results->contains($post));
    }

    public function test_respects_limit(): void
    {
        $category = PostCategory::factory()->published()->create();
        $post = Post::factory()->published()->for($category, 'category')->create();
        Post::factory()->published()->for($category, 'category')->count(6)->create();

        $results = (new GetRelatedPosts)->handle($post, 4);

        $this->assertCount(4, $results);
    }

    public function test_returns_empty_when_post_has_no_category_or_tags(): void
    {
        $post = Post::factory()->published()->create();
        Post::factory()->published()->count(3)->create();

        $results = (new GetRelatedPosts)->handle($post);

        $this->assertTrue($results->isEmpty());
    }
}
