<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_200_and_lists_published_posts(): void
    {
        $post = Post::factory()->published()->create();

        $this->get('/tin-tuc')->assertStatus(200)->assertSee($post->title);
    }

    public function test_index_does_not_list_draft_posts(): void
    {
        $post = Post::factory()->create();

        $this->get('/tin-tuc')->assertDontSee($post->title);
    }

    public function test_show_returns_200_for_published_post(): void
    {
        $post = Post::factory()->published()->create();

        $this->get("/tin-tuc/{$post->slug}")->assertStatus(200)->assertSee($post->title);
    }

    public function test_show_returns_404_for_draft_post(): void
    {
        $post = Post::factory()->create();

        $this->get("/tin-tuc/{$post->slug}")->assertStatus(404);
    }

    public function test_show_returns_404_for_post_scheduled_in_the_future(): void
    {
        $post = Post::factory()->published()->create([
            'published_at' => now()->addDay(),
        ]);

        $this->get("/tin-tuc/{$post->slug}")->assertStatus(404);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/tin-tuc/khong-ton-tai')->assertStatus(404);
    }
}
