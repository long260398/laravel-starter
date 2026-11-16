<?php

namespace App\Support\Theme;

use Illuminate\Support\Facades\Log;

/**
 * Tính theme đang active cho frontend. Theme được khai báo qua `APP_THEME`
 * nhưng nếu thư mục `{theme.path}/{active}/views` không tồn tại thì rơi về
 * `theme.default` + ghi log cảnh báo, để một theme bị xoá/đặt sai tên không
 * bao giờ làm site sập — xem docs/THEME-CONTRACT.md.
 */
class ActiveTheme
{
    private string $name;

    public function __construct(
        private readonly string $requested,
        private readonly string $default,
        private readonly string $basePath,
    ) {
        $this->name = $this->resolve();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function defaultName(): string
    {
        return $this->default;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function viewsPath(?string $theme = null): string
    {
        return $this->basePath.'/'.($theme ?? $this->name).'/views';
    }

    public function cssEntry(?string $theme = null): string
    {
        return 'resources/themes/'.($theme ?? $this->name).'/css/app.css';
    }

    public function jsEntry(?string $theme = null): string
    {
        return 'resources/themes/'.($theme ?? $this->name).'/js/app.js';
    }

    private function resolve(): string
    {
        if (is_dir($this->viewsPath($this->requested))) {
            return $this->requested;
        }

        if ($this->requested !== $this->default) {
            Log::warning("Theme [{$this->requested}] không tồn tại (thiếu thư mục views), rơi về theme mặc định [{$this->default}].");
        }

        return $this->default;
    }
}
