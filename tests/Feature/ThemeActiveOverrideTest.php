<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * APP_THEME=starter-alt chỉ cho riêng test class này (theo đúng pattern
 * ServiceModuleDisabledTest — set env TRƯỚC parent::setUp() để app boot với
 * đúng giá trị). starter-alt chỉ override layout + home (tối giản, xem
 * resources/themes/starter-alt) — mọi view khác phải fallback về default.
 */
class ThemeActiveOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('APP_THEME=starter-alt');
        $_ENV['APP_THEME'] = 'starter-alt';
        $_SERVER['APP_THEME'] = 'starter-alt';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('APP_THEME=default');
        $_ENV['APP_THEME'] = 'default';
        $_SERVER['APP_THEME'] = 'default';

        parent::tearDown();
    }

    public function test_home_page_uses_the_active_themes_own_view(): void
    {
        $this->get('/')->assertOk()->assertSee('Starter Alt');
    }

    public function test_blog_index_falls_back_to_default_theme_view(): void
    {
        $post = Post::factory()->published()->create();

        $this->get('/blog')
            ->assertOk()
            ->assertSee($post->title)
            ->assertDontSee('Starter Alt');
    }
}
