<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Chống spam dùng chung cho form public (Liên hệ, Đặt lịch...): 1 field
 * honeypot phải để trống (chỉ bot mới điền) + 1 token mã hoá thời điểm render
 * form, chỉ chấp nhận submit sau tối thiểu vài giây và trước khi token quá
 * cũ. Controller dùng trait tự gọi các hàm dưới, không đổi hành vi so với
 * bản gốc từng viết riêng trong ContactMessageController.
 */
trait GuardsAntiSpamForm
{
    protected function honeypotField(): string
    {
        return 'website';
    }

    protected function tokenField(): string
    {
        return 'form_token';
    }

    protected function antiSpamMinSeconds(): int
    {
        return 3;
    }

    protected function antiSpamMaxAgeSeconds(): int
    {
        return 7200;
    }

    protected function makeAntiSpamToken(): string
    {
        return Crypt::encrypt(now()->timestamp);
    }

    protected function isHoneypotFilled(Request $request): bool
    {
        return filled($request->input($this->honeypotField()));
    }

    protected function hasFreshAntiSpamToken(Request $request): bool
    {
        $token = $request->input($this->tokenField());

        if (blank($token)) {
            return false;
        }

        try {
            $renderedAt = (int) Crypt::decrypt($token);
        } catch (DecryptException) {
            return false;
        }

        $elapsed = now()->timestamp - $renderedAt;

        return $elapsed >= $this->antiSpamMinSeconds() && $elapsed <= $this->antiSpamMaxAgeSeconds();
    }
}
