<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Laravel resolve errors::404/errors::500 qua namespace `errors` (xem
 * Illuminate\Foundation\Exceptions\RegisterErrorViewPaths — dựng lại hoàn
 * toàn từ config('view.paths') mỗi lần render exception), KHÔNG qua
 * View::getFinder()->prependLocation() của namespace mặc định. ThemeViewResolver
 * ghi đè cả config('view.paths') nên errors/404,500 của theme vẫn fallback
 * đúng thứ tự [theme active, theme default, resources/views].
 */
class ThemeErrorPageFallbackTest extends TestCase
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

    public function test_404_falls_back_to_default_themes_error_page_when_active_theme_has_no_override(): void
    {
        $this->get('/khong-ton-tai-xyz')
            ->assertStatus(404)
            ->assertSee('Về trang chủ');
    }

    public function test_404_uses_the_active_themes_own_error_page_when_it_overrides_it(): void
    {
        $overrideDir = resource_path('themes/starter-alt/views/errors');
        File::ensureDirectoryExists($overrideDir);
        File::put($overrideDir.'/404.blade.php', '<p>Starter Alt 404 riêng</p>');

        try {
            $this->get('/khong-ton-tai-xyz')
                ->assertStatus(404)
                ->assertSee('Starter Alt 404 riêng')
                ->assertDontSee('Về trang chủ');
        } finally {
            File::deleteDirectory($overrideDir);
        }
    }
}
