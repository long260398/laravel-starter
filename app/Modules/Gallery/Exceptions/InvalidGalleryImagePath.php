<?php

namespace App\Modules\Gallery\Exceptions;

use InvalidArgumentException;

/**
 * Ném khi SyncGalleryAlbumImages nhận một path nằm ngoài "gallery/images/"
 * hoặc có dấu hiệu path traversal — path do form gửi lên không bao giờ được
 * tin dùng trực tiếp để ghi DB/xóa file trên disk mà không kiểm tra trước.
 */
class InvalidGalleryImagePath extends InvalidArgumentException
{
    public function __construct(string $path)
    {
        parent::__construct("Đường dẫn ảnh không hợp lệ: {$path}");
    }
}
