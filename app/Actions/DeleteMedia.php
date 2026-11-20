<?php

namespace App\Actions;

use App\Exceptions\MediaInUseException;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Xóa 1 file khỏi kho media — chỉ khi không còn usage nào. Khóa dòng `media`
 * (`lockForUpdate`) trước khi kiểm tra usage để tránh race condition với một
 * request khác đang gắn usage mới cùng lúc. DB xóa trong transaction; file
 * trên disk chỉ xóa SAU KHI transaction commit thành công.
 *
 * @throws MediaInUseException
 */
class DeleteMedia
{
    public function handle(Media $media): void
    {
        DB::transaction(function () use ($media): void {
            $locked = Media::query()->whereKey($media->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->usages()->exists()) {
                throw new MediaInUseException($locked);
            }

            $locked->delete();
        });

        Storage::disk($media->disk)->delete($media->path);
    }
}
