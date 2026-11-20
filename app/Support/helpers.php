<?php

use App\Models\Setting;
use App\Support\Modules\ModuleRegistry;
use App\Support\Theme\ActiveTheme;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('public_file_url')) {
    /**
     * URL công khai của một file lưu trên disk (logo, ảnh banner, ảnh bài viết...).
     * Không truyền `$disk` → dùng `config('media.disk')`.
     */
    function public_file_url(?string $path, ?string $disk = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk($disk ?? config('media.disk'))->url($path);
    }
}

if (! function_exists('module_registry')) {
    function module_registry(): ModuleRegistry
    {
        return app(ModuleRegistry::class);
    }
}

if (! function_exists('active_theme')) {
    function active_theme(): ActiveTheme
    {
        return app(ActiveTheme::class);
    }
}
