<?php

namespace Tests\Unit;

use App\Support\Theme\ActiveTheme;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ActiveTheme singleton bị resolve ngay lúc ThemeServiceProvider::boot() —
 * nghĩa là Log::spy() đặt trong một HTTP-feature-test sẽ luôn thấy 0 lần gọi
 * (warning đã log xong trước khi test method chạy). Test trực tiếp class
 * này, tự dựng Log::spy() TRƯỚC khi khởi tạo, để kiểm được đúng hành vi.
 */
class ActiveThemeTest extends TestCase
{
    public function test_falls_back_to_default_and_logs_a_warning_when_theme_directory_is_missing(): void
    {
        Log::spy();

        $theme = new ActiveTheme('theme-does-not-exist', 'default', resource_path('themes'));

        $this->assertSame('default', $theme->name());
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_uses_the_requested_theme_without_logging_when_it_exists(): void
    {
        Log::spy();

        $theme = new ActiveTheme('default', 'default', resource_path('themes'));

        $this->assertSame('default', $theme->name());
        Log::shouldNotHaveReceived('warning');
    }
}
