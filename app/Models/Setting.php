<?php

namespace App\Models;

use App\Actions\RegisterMediaUsage;
use App\Actions\ReleaseMediaUsage;
use App\Contracts\HasMediaUsageLabel;
use App\Filament\Pages\Settings as SettingsPage;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Lưu cấu hình website dạng key–value. `value` được JSON-encode nên có thể
 * chứa chuỗi, số, boolean hoặc mảng.
 *
 * Các key trong `$fileKeys` được coi là đường dẫn file trên disk
 * `config('media.disk')`: khi giá trị của key đó thay đổi, usage trong kho
 * media được cập nhật tương ứng (xem App\Models\Concerns\TracksMediaUsage) —
 * file không bị xóa ở đây nữa, chỉ xóa từ trang Thư viện media khi hết usage.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model implements HasMediaUsageLabel
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

    /**
     * @return array<int, string>
     */
    public static function fileKeys(): array
    {
        return static::$fileKeys;
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

        $setting = static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        if (! in_array($key, static::$fileKeys, true)) {
            return;
        }

        if (filled($value)) {
            app(RegisterMediaUsage::class)->handle(config('media.disk'), $value, $setting, $key);
        }

        if (filled($oldValue) && $oldValue !== $value) {
            app(ReleaseMediaUsage::class)->handle(config('media.disk'), $oldValue, $setting, $key);
        }
    }

    public function mediaUsageLabel(): string
    {
        return 'Cài đặt → '.$this->key;
    }

    public function mediaUsageUrl(): ?string
    {
        return SettingsPage::getUrl();
    }
}
