<?php

namespace App\Modules\Booking\Actions;

use App\Models\Setting;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\BookingRateLimitExceeded;
use App\Modules\Booking\Mail\NewBookingRequestMail;
use App\Modules\Booking\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SubmitBookingRequest
{
    /**
     * Lưu 1 yêu cầu đặt lịch mới sau khi chống spam theo IP, rồi gửi mail báo
     * cho admin ở background (`defer`). Không phụ thuộc HTTP: nhận dữ liệu
     * thuần đã validate, không dùng Request/abort() — lỗi nghiệp vụ ném
     * exception riêng để nơi gọi (web controller) tự chuyển thành response.
     *
     * @param  array<string, mixed>  $data  Dữ liệu đã validate + chuẩn hoá:
     *                                      name, phone, email, service_name, preferred_date, time_slot,
     *                                      time_slot_label, party_size, note.
     *
     * @throws BookingRateLimitExceeded
     */
    public function handle(array $data, string $ip, ?string $userAgent): Booking
    {
        $this->guardRateLimit($ip);

        $booking = Booking::query()->create([
            ...$data,
            'status' => BookingStatus::New,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $this->hitRateLimiters($ip);

        defer(fn (): mixed => $this->notifyAdmin($booking));

        return $booking;
    }

    private function guardRateLimit(string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->perWindowKey($ip), 3)) {
            throw new BookingRateLimitExceeded(RateLimiter::availableIn($this->perWindowKey($ip)));
        }

        if (RateLimiter::tooManyAttempts($this->perDayKey($ip), 10)) {
            throw new BookingRateLimitExceeded(RateLimiter::availableIn($this->perDayKey($ip)));
        }
    }

    private function hitRateLimiters(string $ip): void
    {
        RateLimiter::hit($this->perWindowKey($ip), 600);
        RateLimiter::hit($this->perDayKey($ip), 86400);
    }

    private function perWindowKey(string $ip): string
    {
        return "booking-strict-10min:{$ip}";
    }

    private function perDayKey(string $ip): string
    {
        return "booking-strict-day:{$ip}";
    }

    private function notifyAdmin(Booking $booking): void
    {
        $recipient = Setting::get('contact_email');

        if (blank($recipient)) {
            Log::warning('Chưa cấu hình email nhận liên hệ (contact_email) trong Cài đặt website.');

            return;
        }

        try {
            Mail::to($recipient)->send(new NewBookingRequestMail($booking));
        } catch (Throwable $e) {
            Log::error('Gửi email thông báo đặt lịch thất bại.', [
                'booking_id' => $booking->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
