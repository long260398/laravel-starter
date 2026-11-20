<?php

namespace App\Modules\Gallery\Actions;

use App\Modules\Gallery\Exceptions\InvalidGalleryImagePath;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Đồng bộ ảnh của một album theo danh sách path đã upload, đúng thứ tự
 * người dùng sắp xếp trong ô "Ảnh trong album". Thay đổi DB (tạo ảnh mới,
 * cập nhật sort_order, xóa dòng ảnh bị gỡ) chạy trong 1 transaction.
 *
 * Ảnh bị gỡ chỉ bị xóa dòng `gallery_images` — GalleryImage::delete() tự gỡ
 * usage trong kho media qua TracksMediaUsage, KHÔNG xóa file trên disk (file
 * chỉ xóa từ trang Thư viện media khi hết usage). File mới upload trong lần
 * submit này nhưng transaction rollback thì vẫn phải dọn — file đó chưa từng
 * được ghi nhận vào kho media nên không phải "đang track".
 */
class SyncGalleryAlbumImages
{
    /**
     * @param  list<string>  $paths  Path ảnh (disk config('media.disk')) theo thứ tự mong muốn.
     *
     * @throws InvalidGalleryImagePath
     */
    public function handle(GalleryAlbum $album, array $paths): void
    {
        foreach ($paths as $path) {
            $this->assertValidPath($path);
        }

        // Không dùng Collection::except() ở đây: Illuminate\Database\Eloquent\Collection
        // override except()/only() để lọc theo PRIMARY KEY của model, không phải
        // theo key của collection — dùng nó trên collection đã keyBy('image') sẽ
        // luôn không khớp gì (key là path, không phải id) và làm mất key gốc.
        $existing = $album->images()->get()->keyBy('image');
        $toRemove = $existing->reject(fn (GalleryImage $image, string $path): bool => in_array($path, $paths, true));
        $newPaths = array_values(array_diff($paths, $existing->keys()->all()));

        try {
            DB::transaction(function () use ($paths, $album, $existing, $toRemove): void {
                // Xóa từng model (không dùng whereIn()->delete() của query builder)
                // để event `deleted` của từng GalleryImage chạy — TracksMediaUsage
                // cần event đó để gỡ usage trong kho media.
                $toRemove->each(fn (GalleryImage $image) => $image->delete());

                foreach ($paths as $index => $path) {
                    $image = $existing->get($path);

                    if ($image) {
                        $image->update(['sort_order' => $index + 1]);
                    } else {
                        GalleryImage::create([
                            'gallery_album_id' => $album->id,
                            'image' => $path,
                            'sort_order' => $index + 1,
                        ]);
                    }
                }
            });
        } catch (Throwable $e) {
            // Dọn file vừa upload trong lần submit này (chưa từng có trong
            // DB) để không thành file rác — file cũ (đang gắn với ảnh còn
            // lại trong DB sau rollback) giữ nguyên.
            Storage::disk(config('media.disk'))->delete($newPaths);

            throw $e;
        }
    }

    private function assertValidPath(string $path): void
    {
        if (
            str_contains($path, '..')
            || str_starts_with($path, '/')
            || ! str_starts_with($path, 'gallery/images/')
        ) {
            throw new InvalidGalleryImagePath($path);
        }
    }
}
