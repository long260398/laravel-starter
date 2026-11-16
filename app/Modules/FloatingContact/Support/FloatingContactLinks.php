<?php

namespace App\Modules\FloatingContact\Support;

/**
 * Chuyển giá trị thô trong Settings thành link an toàn cho nút liên hệ nổi.
 * Mọi hàm trả `null` khi giá trị rỗng hoặc không hợp lệ — view tự ẩn nút đó.
 */
class FloatingContactLinks
{
    private const string ZALO_PREFIX = 'https://zalo.me/';

    private const string MESSENGER_PREFIX = 'https://m.me/';

    /**
     * Chỉ giữ số và dấu "+" ở đầu. Ví dụ "(028) 1234-5678" -> "tel:02812345678".
     */
    public static function telLink(string $hotline): ?string
    {
        $value = trim($hotline);

        if ($value === '') {
            return null;
        }

        $hasLeadingPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D/', '', $value);

        if (blank($digits)) {
            return null;
        }

        return 'tel:'.($hasLeadingPlus ? '+' : '').$digits;
    }

    /**
     * Nhận `https://zalo.me/{chuỗi chữ-số}` (số điện thoại hoặc ID Official
     * Account) và chặn mọi domain/scheme khác. Nếu Settings lưu số điện
     * thoại thô (không phải URL) thì tự ghép thành `https://zalo.me/{số}`.
     */
    public static function zaloLink(string $value): ?string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, self::ZALO_PREFIX)) {
            $id = substr($trimmed, strlen(self::ZALO_PREFIX));

            return preg_match('/^[A-Za-z0-9]+$/', $id) === 1 ? $trimmed : null;
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $trimmed);

        return blank($digits) ? null : self::ZALO_PREFIX.$digits;
    }

    /**
     * Chỉ chấp nhận `https://m.me/{username hoặc id}`.
     */
    public static function messengerLink(string $value): ?string
    {
        $trimmed = trim($value);

        if (! str_starts_with($trimmed, self::MESSENGER_PREFIX)) {
            return null;
        }

        $id = substr($trimmed, strlen(self::MESSENGER_PREFIX));

        return preg_match('/^[A-Za-z0-9._-]+$/', $id) === 1 ? $trimmed : null;
    }
}
