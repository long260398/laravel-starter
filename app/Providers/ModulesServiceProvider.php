<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Đăng ký ServiceProvider của từng module đang bật trong config/modules.php.
 * Đây là điểm chạm duy nhất trong bootstrap/providers.php cho mọi module —
 * thêm module mới không cần sửa file này, chỉ cần thêm entry trong config.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (config('modules', []) as $module) {
            if ($module['enabled'] ?? false) {
                $this->app->register($module['provider']);
            }
        }
    }
}
