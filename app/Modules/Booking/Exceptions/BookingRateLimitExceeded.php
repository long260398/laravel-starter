<?php

namespace App\Modules\Booking\Exceptions;

use RuntimeException;

/**
 * Ném khi một IP vượt giới hạn gửi yêu cầu đặt lịch. Không phụ thuộc HTTP —
 * nơi gọi (web controller) tự quyết định chuyển thành response nào.
 */
class BookingRateLimitExceeded extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('Đã vượt giới hạn gửi yêu cầu đặt lịch.');
    }
}
