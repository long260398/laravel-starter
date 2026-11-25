<?php

namespace App\Actions;

use App\Contracts\HasMediaUsageLabel;
use App\Exceptions\MediaInUseException;
use App\Models\Media;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Xóa 1 file khỏi kho media.
 *
 * - $force = false (mặc định): chỉ xóa khi không còn usage nào — ném
 *   MediaInUseException nếu còn (hành vi cũ, không đổi).
 * - $force = true: Admin chủ động xóa ảnh đang dùng — gỡ khỏi mọi chỗ dùng
 *   trước (xem HasMediaUsageLabel::releaseMediaOnForceDelete()), rồi mới xóa
 *   dòng `media` (cascade xóa luôn mọi `media_usages` còn sót).
 *
 * Khóa dòng `media` (`lockForUpdate`) trước khi đọc usage để tránh race
 * condition với request khác đang gắn usage mới cùng lúc. DB xóa trong
 * transaction; file trên disk chỉ xóa SAU KHI transaction commit thành công.
 *
 * @throws MediaInUseException
 */
class DeleteMedia
{
    /**
     * @return array{detached: int, notes: array<int, string>, locations: array<int, string>}
     */
    public function handle(Media $media, bool $force = false): array
    {
        $result = DB::transaction(function () use ($media, $force): array {
            $locked = Media::query()->whereKey($media->getKey())->lockForUpdate()->firstOrFail();
            $usages = $locked->usages()->with('usable')->get();

            if ($usages->isNotEmpty() && ! $force) {
                throw new MediaInUseException($locked);
            }

            $notes = [];
            $locations = [];

            foreach ($usages as $usage) {
                $usable = $usage->usable;

                if (! $usable instanceof HasMediaUsageLabel) {
                    $usage->delete();

                    continue;
                }

                // Lấy label TRƯỚC khi gỡ — sau khi gỡ, $usable có thể đã bị xóa
                // (vd GalleryImage) hoặc đổi trạng thái nên không còn tin cậy.
                $locations[] = "{$usage->field} @ {$usable->mediaUsageLabel()}";

                $note = $usable->releaseMediaOnForceDelete($usage->field, $locked);

                if ($note !== null) {
                    $notes[] = $note;
                }
            }

            $locked->delete();

            return ['detached' => $usages->count(), 'notes' => $notes, 'locations' => $locations];
        });

        Storage::disk($media->disk)->delete($media->path);

        if ($result['detached'] > 0) {
            Log::info('Admin xóa cưỡng ép media đang được dùng', [
                'admin_id' => Auth::id(),
                'media_id' => $media->id,
                'disk' => $media->disk,
                'path' => $media->path,
                'detached' => $result['detached'],
                'locations' => $result['locations'],
                'notes' => $result['notes'],
            ]);
        }

        return $result;
    }
}
