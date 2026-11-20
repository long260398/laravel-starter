<?php

namespace App\Actions;

use App\Models\Media;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;

/**
 * Gỡ usage của `$usable` khỏi 1 file — không đụng tới file trên disk hay
 * dòng `media` (file có thể còn usage khác, hoặc chỉ tạm thành "chưa dùng").
 * Không tìm thấy Media/usage tương ứng thì bỏ qua (no-op).
 */
class ReleaseMediaUsage
{
    public function handle(string $disk, string $path, Model $usable, string $field): void
    {
        $media = Media::query()->where('disk', $disk)->where('path', $path)->first();

        if ($media === null) {
            return;
        }

        MediaUsage::query()
            ->where('media_id', $media->id)
            ->where('usable_type', $usable::class)
            ->where('usable_id', $usable->getKey())
            ->where('field', $field)
            ->delete();
    }
}
