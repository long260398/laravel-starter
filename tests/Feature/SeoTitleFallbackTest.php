<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTitleFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_title_falls_back_to_app_name_when_site_name_not_set(): void
    {
        $response = $this->get('/');

        $response->assertSee('<title>'.config('app.name').'</title>', false);
    }

    public function test_post_title_has_no_dangling_dash_when_site_name_not_set(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Tin ABC']);

        $response = $this->get("/blog/{$post->slug}");

        $response->assertSee('<title>Tin ABC - '.config('app.name').'</title>', false);
        $response->assertDontSee('<title>Tin ABC - </title>', false);
    }
}
