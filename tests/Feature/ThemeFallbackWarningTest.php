<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTemporaryEnv;
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
    use RefreshDatabase, WithTemporaryEnv;

    protected function setUp(): void
    {
        $this->setTemporaryEnv(['APP_THEME' => 'theme-does-not-exist']);

        parent::setUp();
    }

    public function test_home_page_falls_back_to_default_theme(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Starter Alt');
    }
}
