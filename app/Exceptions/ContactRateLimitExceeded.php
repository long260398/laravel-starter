<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Ném khi một IP vượt giới hạn gửi liên hệ. Không phụ thuộc HTTP — nơi gọi
 * (web controller, API sau này) tự quyết định chuyển thành response nào.
 */
class ContactRateLimitExceeded extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('Đã vượt giới hạn gửi liên hệ.');
    }
}
