<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('public_file_url')) {
    /**
     * URL công khai của một file lưu trên disk `public` (logo, ảnh banner, ảnh bài viết...).
     */
    function public_file_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
