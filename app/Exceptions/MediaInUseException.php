<?php

namespace App\Exceptions;

use App\Models\Media;
use RuntimeException;

class MediaInUseException extends RuntimeException
{
    public function __construct(Media $media)
    {
        parent::__construct("Không thể xóa file đang được dùng (media #{$media->id}).");
    }
}
