<?php

namespace App\Providers;

use App\Support\Theme\ActiveTheme;
use App\Support\Theme\ThemeViewResolver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Đăng ký cuối cùng trong bootstrap/providers.php (sau ModulesServiceProvider):
 * resolve view của theme không phụ thuộc module provider boot trước hay sau
 * (xem ThemeViewResolver::applyModuleOverrides, dùng path khai báo sẵn trong
 * config('modules') thay vì đọc lại view path module đã đăng ký).
 */
class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActiveTheme::class, fn () => new ActiveTheme(
            config('theme.active'),
            config('theme.default'),
            config('theme.path'),
        ));
    }

    public function boot(): void
    {
        $activeTheme = $this->app->make(ActiveTheme::class);

        $this->app->make(ThemeViewResolver::class)->apply($activeTheme->name());

        View::share('activeTheme', $activeTheme->name());
    }
}
