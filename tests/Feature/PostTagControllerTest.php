<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_page_returns_200_and_lists_only_published_posts_with_tag(): void
    {
        $tag = Tag::factory()->create();
        $otherTag = Tag::factory()->create();

        $published = Post::factory()->published()->create();
        $published->tags()->attach($tag);

        $draft = Post::factory()->create();
        $draft->tags()->attach($tag);

        $otherTagPost = Post::factory()->published()->create();
        $otherTagPost->tags()->attach($otherTag);

        $response = $this->get("/blog/tag/{$tag->slug}");

        $response->assertStatus(200)
            ->assertSee($published->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($otherTagPost->title);
    }

    public function test_tag_page_returns_404_for_unknown_slug(): void
    {
        $this->get('/blog/tag/khong-ton-tai')->assertStatus(404);
    }

    public function test_tag_page_is_noindex_when_fewer_than_3_posts(): void
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->published()->create();
        $post->tags()->attach($tag);

        $response = $this->get("/blog/tag/{$tag->slug}");

        $response->assertSee('noindex', false);
    }

    public function test_tag_page_is_not_noindex_when_at_least_3_posts(): void
    {
        $tag = Tag::factory()->create();

        Post::factory()->published()->count(3)->create()->each(
            fn (Post $post) => $post->tags()->attach($tag)
        );

        $response = $this->get("/blog/tag/{$tag->slug}");

        $response->assertDontSee('name="robots" content="noindex,follow"', false);
    }
}
