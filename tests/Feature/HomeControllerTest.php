<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_200(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_home_page_shows_visible_slider_banner(): void
    {
        $banner = Banner::factory()->published()->forPosition('home_slider')->create();

        $this->get('/')->assertSee($banner->title);
    }

    public function test_home_page_shows_latest_published_post(): void
    {
        $post = Post::factory()->published()->create();

        $this->get('/')->assertSee($post->title);
    }

    public function test_home_page_does_not_show_draft_post(): void
    {
        $post = Post::factory()->create();

        $this->get('/')->assertDontSee($post->title);
    }
}
