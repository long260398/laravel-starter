<?php

namespace App\Actions;

use App\Enums\ContactStatus;
use App\Exceptions\ContactRateLimitExceeded;
use App\Mail\NewContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SubmitContactMessage
{
    /**
     * Lưu một liên hệ mới sau khi chống spam theo IP, rồi gửi mail báo cho
     * admin ở background (`defer`). Không phụ thuộc HTTP: nhận dữ liệu thuần
     * đã validate, không dùng Request/abort() — lỗi nghiệp vụ ném exception
     * riêng để nơi gọi (web, API sau này) tự chuyển thành response phù hợp.
     *
     * @param  array<string, mixed>  $data  Dữ liệu đã validate: name, phone, email, subject, message.
     *
     * @throws ContactRateLimitExceeded
     */
    public function handle(array $data, string $ip, ?string $userAgent): ContactMessage
    {
        $this->guardRateLimit($ip);

        $contactMessage = ContactMessage::query()->create([
            ...$data,
            'status' => ContactStatus::New,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $this->hitRateLimiters($ip);

        defer(fn (): mixed => $this->notifyAdmin($contactMessage));

        return $contactMessage;
    }

    private function guardRateLimit(string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->perWindowKey($ip), 3)) {
            throw new ContactRateLimitExceeded(RateLimiter::availableIn($this->perWindowKey($ip)));
        }

        if (RateLimiter::tooManyAttempts($this->perDayKey($ip), 10)) {
            throw new ContactRateLimitExceeded(RateLimiter::availableIn($this->perDayKey($ip)));
        }
    }

    private function hitRateLimiters(string $ip): void
    {
        RateLimiter::hit($this->perWindowKey($ip), 600);
        RateLimiter::hit($this->perDayKey($ip), 86400);
    }

    private function perWindowKey(string $ip): string
    {
        return "contact-strict-10min:{$ip}";
    }

    private function perDayKey(string $ip): string
    {
        return "contact-strict-day:{$ip}";
    }

    private function notifyAdmin(ContactMessage $contactMessage): void
    {
        $recipient = Setting::get('contact_email');

        if (blank($recipient)) {
            Log::warning('Chưa cấu hình email nhận liên hệ (contact_email) trong Cài đặt website.');

            return;
        }

        try {
            Mail::to($recipient)->send(new NewContactMessageMail($contactMessage));
        } catch (Throwable $e) {
            Log::error('Gửi email thông báo liên hệ thất bại.', [
                'contact_message_id' => $contactMessage->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
