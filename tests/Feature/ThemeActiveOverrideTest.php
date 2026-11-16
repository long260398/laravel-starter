<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTemporaryEnv;
use Tests\TestCase;

/**
 * APP_THEME=starter-alt chỉ cho riêng test class này (set env TRƯỚC
 * parent::setUp() để app boot với đúng giá trị — xem Tests\Concerns\
 * WithTemporaryEnv). starter-alt chỉ override layout + home (tối giản, xem
 * resources/themes/starter-alt) — mọi view khác phải fallback về default.
 */
class ThemeActiveOverrideTest extends TestCase
{
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        $this->setTemporaryEnv(['APP_THEME' => 'starter-alt']);

        parent::setUp();
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
