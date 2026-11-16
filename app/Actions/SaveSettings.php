<?php

namespace App\Actions;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SaveSettings
{
    /**
     * Lưu nhiều key cài đặt trong 1 transaction. File cũ bị thay chỉ được
     * xóa sau khi transaction commit thành công (gom danh sách, xóa sau
     * cùng) — lỗi giữa chừng sẽ rollback DB và không đụng tới file nào.
     * Cache chỉ bị forget 1 lần sau cùng, không phải mỗi key.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $filesToDelete = DB::transaction(function () use ($data): array {
            $filesToDelete = [];

            Setting::withoutEvents(function () use ($data, &$filesToDelete): void {
                foreach ($data as $key => $value) {
                    $oldValue = Setting::query()->where('key', $key)->first()?->value;

                    Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

                    if (in_array($key, Setting::fileKeys(), true) && filled($oldValue) && $oldValue !== $value) {
                        $filesToDelete[] = $oldValue;
                    }
                }
            });

            return $filesToDelete;
        });

        foreach ($filesToDelete as $path) {
            Storage::disk('public')->delete($path);
        }

        Cache::forget(Setting::CACHE_KEY);
    }
}
