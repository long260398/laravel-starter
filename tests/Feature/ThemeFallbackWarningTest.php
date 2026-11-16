<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * APP_THEME trỏ tới theme không tồn tại — App\Support\Theme\ActiveTheme phải
 * rơi về theme `default` (không được sập site). Việc ghi log cảnh báo được
 * test riêng ở Tests\Unit\ActiveThemeTest (ActiveTheme là singleton, resolve
 * ngay lúc boot — Log::spy() đặt trong test HTTP như thế này không thấy
 * được lệnh log đã chạy trước đó).
 */
class ThemeFallbackWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('APP_THEME=theme-does-not-exist');
        $_ENV['APP_THEME'] = 'theme-does-not-exist';
        $_SERVER['APP_THEME'] = 'theme-does-not-exist';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('APP_THEME=default');
        $_ENV['APP_THEME'] = 'default';
        $_SERVER['APP_THEME'] = 'default';

        parent::tearDown();
    }

    public function test_home_page_falls_back_to_default_theme(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Starter Alt');
    }
}
