<?php

namespace App\Actions;

use App\Models\Media;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Đăng ký 1 file đã tồn tại trên disk vào kho media nếu chưa có (idempotent
 * theo unique `disk`+`path`) — không gắn usage nào. Dùng {@see RegisterMediaUsage}
 * khi cần đăng ký kèm gắn usage.
 */
class RegisterMedia
{
    public function handle(string $disk, string $path): Media
    {
        $existing = Media::query()->where('disk', $disk)->where('path', $path)->first();

        if ($existing !== null) {
            return $existing;
        }

        $metadata = $this->readMetadata($disk, $path);

        return Media::create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => basename($path),
            // Auth::id() trả null khi không có người dùng đăng nhập (ví dụ
            // lệnh `media:sync` chạy từ CLI) — media:sync vẫn gọi action này
            // nên để nguyên, không cần tách riêng.
            'uploaded_by' => Auth::id(),
            ...$metadata,
        ]);
    }

    /**
     * Đọc mime/size/kích thước ảnh của `$path` — không chặn việc đăng ký nếu
     * file không đọc được (ví dụ race condition, hoặc dữ liệu test không có
     * file thật trên disk). Đăng ký usage không được phép làm fail thao tác
     * save chính của model.
     *
     * @return array{mime_type: string, size: int, width: ?int, height: ?int}
     */
    private function readMetadata(string $disk, string $path): array
    {
        $storage = Storage::disk($disk);

        try {
            $size = $storage->size($path);
            $mimeType = $storage->mimeType($path) ?: 'application/octet-stream';
            $content = $storage->get($path);
        } catch (Throwable $e) {
            Log::warning('Không đọc được metadata file khi đăng ký media.', [
                'disk' => $disk,
                'path' => $path,
                'exception' => $e->getMessage(),
            ]);

            return ['mime_type' => 'application/octet-stream', 'size' => 0, 'width' => null, 'height' => null];
        }

        $dimensions = $content !== null ? @getimagesizefromstring($content) : false;

        return [
            'mime_type' => $mimeType,
            'size' => $size,
            'width' => $dimensions !== false ? $dimensions[0] : null,
            'height' => $dimensions !== false ? $dimensions[1] : null,
        ];
    }
}
