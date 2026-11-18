<?php

namespace App\Support\Theme;

use Illuminate\Contracts\View\Factory;

/**
 * Nối theme vào cơ chế resolve view của Laravel mà không bắt controller/module
 * đổi tên view (`view('home')`, `view('services::index')` giữ nguyên).
 *
 * - View không namespace (layout, home, blog...): prepend path của theme
 *   đang active rồi tới theme mặc định vào finder — Laravel tự tìm theo thứ
 *   tự đó, thiếu ở theme active thì rơi xuống theme mặc định. `config('view.
 *   paths')` cũng được ghi đè tương ứng vì Laravel tự dựng lại namespace
 *   `errors::` từ `config('view.paths')` mỗi lần render exception (xem
 *   Illuminate\Foundation\Exceptions\RegisterErrorViewPaths) — nhờ vậy
 *   errors/404,500 của theme cũng fallback đúng thứ tự.
 * - View namespace của module (`services::`, `gallery::`): module vẫn tự
 *   `loadViewsFrom` như cũ; ở đây chỉ `replaceNamespace` để chèn thư mục
 *   override của theme (`{theme}/views/modules/{module}`) lên trước view gốc của
 *   module — không cần quan tâm module provider boot trước hay sau.
 */
class ThemeViewResolver
{
    public function __construct(private readonly Factory $view) {}

    public function apply(string $theme): void
    {
        $default = config('theme.default');
        $base = config('theme.path');

        $paths = array_values(array_unique(array_filter([
            "{$base}/{$theme}/views",
            "{$base}/{$default}/views",
            resource_path('views'),
        ], fn (string $path): bool => is_dir($path))));

        config(['view.paths' => $paths]);

        $finder = $this->view->getFinder();

        foreach (array_reverse($paths) as $path) {
            $finder->prependLocation($path);
        }

        $this->applyModuleOverrides($theme, $default, $base);
    }

    private function applyModuleOverrides(string $theme, string $default, string $base): void
    {
        $finder = $this->view->getFinder();

        foreach (config('modules', []) as $key => $module) {
            if (! ($module['enabled'] ?? false)) {
                continue;
            }

            $originalPath = $module['path'].'/resources/views';

            $overrides = array_values(array_filter([
                "{$base}/{$theme}/views/modules/{$key}",
                $theme !== $default ? "{$base}/{$default}/views/modules/{$key}" : null,
            ], fn (?string $path): bool => $path !== null && is_dir($path)));

            $finder->replaceNamespace($key, [...$overrides, $originalPath]);
        }
    }
}
