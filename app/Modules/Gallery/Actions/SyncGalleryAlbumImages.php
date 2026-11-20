<?php

namespace App\Modules\Gallery\Actions;

use App\Actions\RegisterMedia;
use App\Models\Media;
use App\Modules\Gallery\Exceptions\InvalidGalleryImagePath;
use App\Modules\Gallery\Models\GalleryAlbum;
use App\Modules\Gallery\Models\GalleryImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Đồng bộ ảnh của một album theo danh sách path (upload mới HOẶC chọn từ kho
 * media có sẵn — xem App\Filament\Forms\Components\MediaPicker), đúng thứ tự
 * người dùng sắp xếp trong ô "Ảnh trong album". Thay đổi DB (tạo ảnh mới, cập
 * nhật sort_order, xóa dòng ảnh bị gỡ) chạy trong 1 transaction.
 *
 * Ảnh bị gỡ chỉ bị xóa dòng `gallery_images` — GalleryImage::delete() tự gỡ
 * usage trong kho media qua TracksMediaUsage, KHÔNG xóa file trên disk (file
 * chỉ xóa từ trang Thư viện media khi hết usage).
 *
 * Đăng ký ảnh mới vào kho media chạy TRƯỚC khi mở transaction gắn ảnh vào
 * album — nếu bước gắn vào album sau đó lỗi (rollback), dòng `media` của ảnh
 * mới vẫn còn, hiện "Chưa dùng" trong Thư viện media, không bị mất dấu như
 * file không ai biết. Không cần dọn file thủ công ở đây nữa: mọi field
 * MediaPicker khác trong app đã để file "chưa dùng" như vậy, Gallery không
 * cần xử lý riêng.
 */
class SyncGalleryAlbumImages
{
    /**
     * @var array<int, string>
     */
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @param  list<string>  $paths  Path ảnh theo thứ tự mong muốn — có thể là ảnh vừa
     *                               upload mới (thư mục gallery/images/) hoặc path của
     *                               ảnh đã có sẵn trong kho media (chọn từ thư viện).
     *
     * @throws InvalidGalleryImagePath
     */
    public function handle(GalleryAlbum $album, array $paths): void
    {
        $existing = $album->images()->get()->keyBy('image');
        $newPaths = array_values(array_diff($paths, $existing->keys()->all()));

        foreach ($newPaths as $path) {
            $this->registerIfFreshUpload($path);
        }

        foreach ($paths as $path) {
            $this->assertValidPath($path);
        }

        // Không dùng Collection::except() ở đây: Illuminate\Database\Eloquent\Collection
        // override except()/only() để lọc theo PRIMARY KEY của model, không phải
        // theo key của collection — dùng nó trên collection đã keyBy('image') sẽ
        // luôn không khớp gì (key là path, không phải id) và làm mất key gốc.
        $toRemove = $existing->reject(fn (GalleryImage $image, string $path): bool => in_array($path, $paths, true));

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
    }

    /**
     * Ảnh mới (chưa có dòng `media`) chỉ được đăng ký nếu thật sự là file vừa
     * upload qua ô này: nằm trong thư mục cấu hình, tồn tại trên disk, mime
     * thật (đọc qua Storage, không tin theo đuôi file client gửi) là ảnh được
     * phép. Ảnh đã có dòng `media` (chọn từ thư viện) bỏ qua — khỏi đăng ký lại.
     */
    private function registerIfFreshUpload(string $path): void
    {
        $disk = config('media.disk');

        if (Media::query()->where('disk', $disk)->where('path', $path)->exists()) {
            return;
        }

        if (str_contains($path, '..') || str_starts_with($path, '/') || ! str_starts_with($path, 'gallery/images/')) {
            return;
        }

        try {
            if (! Storage::disk($disk)->exists($path)) {
                return;
            }

            $mimeType = Storage::disk($disk)->mimeType($path);
        } catch (Throwable) {
            return;
        }

        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return;
        }

        app(RegisterMedia::class)->handle($disk, $path);
    }

    private function assertValidPath(string $path): void
    {
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new InvalidGalleryImagePath($path);
        }

        $exists = Media::query()
            ->where('disk', config('media.disk'))
            ->where('path', $path)
            ->whereIn('mime_type', self::ALLOWED_MIME_TYPES)
            ->exists();

        if (! $exists) {
            throw new InvalidGalleryImagePath($path);
        }
    }
}
