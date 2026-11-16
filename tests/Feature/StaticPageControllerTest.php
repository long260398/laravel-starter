<?php

namespace Tests\Feature;

use App\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_200_for_published_page(): void
    {
        $page = StaticPage::factory()->published()->create();

        $this->get("/{$page->slug}")->assertStatus(200)->assertSee($page->title);
    }

    public function test_show_returns_404_for_draft_page(): void
    {
        $page = StaticPage::factory()->create();

        $this->get("/{$page->slug}")->assertStatus(404);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/khong-ton-tai')->assertStatus(404);
    }

    public function test_slug_matching_a_known_route_is_handled_by_that_route_not_static_page(): void
    {
        $this->get('/blog')->assertStatus(200);
        $this->get('/contact')->assertStatus(200);
    }
}
