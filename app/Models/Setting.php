<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Lưu cấu hình website dạng key–value. `value` được JSON-encode nên có thể
 * chứa chuỗi, số, boolean hoặc mảng.
 *
 * Các key trong `$fileKeys` được coi là đường dẫn file trên disk `public`:
 * khi giá trị của key đó thay đổi, file cũ sẽ bị xóa sau khi DB ghi thành công.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const string CACHE_KEY = 'settings.all';

    /**
     * @var array<int, string>
     */
    protected static array $fileKeys = ['logo', 'favicon', 'og_image'];

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()->get()->pluck('value', 'key')->all(),
        );

        return $all[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $oldValue = static::query()->where('key', $key)->first()?->value;

        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        if (! in_array($key, static::$fileKeys, true)) {
            return;
        }

        if (blank($oldValue) || $oldValue === $value) {
            return;
        }

        Storage::disk('public')->delete($oldValue);
    }
}
