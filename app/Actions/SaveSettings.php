<?php

namespace App\Actions;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SaveSettings
{
    /**
     * Lưu nhiều key cài đặt trong 1 transaction. Usage trong kho media của
     * key file được đăng ký/gỡ trong CÙNG transaction (xem App\Models\Media) —
     * lỗi giữa chừng rollback DB thì usage cũng rollback theo, không có file
     * nào bị xóa ở đây (chỉ xóa từ trang Thư viện media khi hết usage).
     * Cache chỉ bị forget 1 lần sau cùng, không phải mỗi key.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        DB::transaction(function () use ($data): void {
            Setting::withoutEvents(function () use ($data): void {
                foreach ($data as $key => $value) {
                    $oldValue = Setting::query()->where('key', $key)->first()?->value;

                    $setting = Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

                    if (! in_array($key, Setting::fileKeys(), true)) {
                        continue;
                    }

                    if (filled($value)) {
                        app(RegisterMediaUsage::class)->handle(config('media.disk'), $value, $setting, $key);
                    }

                    if (filled($oldValue) && $oldValue !== $value) {
                        app(ReleaseMediaUsage::class)->handle(config('media.disk'), $oldValue, $setting, $key);
                    }
                }
            });
        });

        Cache::forget(Setting::CACHE_KEY);
    }
}
