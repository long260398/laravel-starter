<?php

namespace App\Actions;

use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;

/**
 * Đồng bộ usage kiểu "nhiều ảnh trong 1 field HTML" (RichEditor) — khác với
 * TracksMediaUsage (1 field = 1 path). So khớp danh sách ảnh hiện có trong
 * HTML với usage đã ghi cho field đó: đăng ký ảnh mới, gỡ ảnh không còn xuất
 * hiện. Gọi với $html = '' (hoặc không gọi handle, truyền chuỗi rỗng) để gỡ
 * hết — dùng khi model bị xóa hẳn.
 */
class SyncContentMediaUsage
{
    public function handle(Model $model, string $field, string $html, string $disk): void
    {
        $newPaths = app(ExtractContentMediaPaths::class)->handle($html, $disk);

        $currentPaths = MediaUsage::query()
            ->where('usable_type', $model::class)
            ->where('usable_id', $model->getKey())
            ->where('field', $field)
            ->with('media')
            ->get()
            ->pluck('media.path')
            ->filter()
            ->all();

        foreach (array_diff($newPaths, $currentPaths) as $path) {
            app(RegisterMediaUsage::class)->handle($disk, $path, $model, $field);
        }

        foreach (array_diff($currentPaths, $newPaths) as $path) {
            app(ReleaseMediaUsage::class)->handle($disk, $path, $model, $field);
        }
    }
}
