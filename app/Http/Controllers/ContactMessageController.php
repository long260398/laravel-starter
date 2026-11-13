<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\NewContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class ContactMessageController extends Controller
{
    /**
     * Tên field honeypot: phải để trống, chỉ bot mới điền.
     */
    private const string HONEYPOT_FIELD = 'website';

    /**
     * Tên field chứa thời điểm render form, đã mã hóa.
     */
    private const string TOKEN_FIELD = 'form_token';

    private const int MIN_SUBMIT_SECONDS = 3;

    private const int MAX_TOKEN_AGE_SECONDS = 7200;

    public function create(): View
    {
        return view('contact.create', [
            'honeypotField' => self::HONEYPOT_FIELD,
            'tokenField' => self::TOKEN_FIELD,
            'formToken' => Crypt::encrypt(now()->timestamp),
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $ip = $request->ip();

        if ($this->tooManyAttempts($ip)) {
            abort(429, 'Bạn đã gửi liên hệ quá nhiều lần, vui lòng thử lại sau.');
        }

        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return $this->successResponse();
        }

        if (! $this->hasFreshToken($request)) {
            return back()->withErrors(['form' => 'Vui lòng thử lại.']);
        }

        $contactMessage = ContactMessage::query()->create([
            ...$request->safe()->only(['name', 'phone', 'email', 'subject', 'message']),
            'status' => ContactStatus::New,
            'ip_address' => $ip,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        $this->hitRateLimiters($ip);

        defer(fn (): mixed => $this->notifyAdmin($contactMessage));

        return $this->successResponse();
    }

    private function tooManyAttempts(?string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->perWindowKey($ip), 3)
            || RateLimiter::tooManyAttempts($this->perDayKey($ip), 10);
    }

    private function hitRateLimiters(?string $ip): void
    {
        RateLimiter::hit($this->perWindowKey($ip), 600);
        RateLimiter::hit($this->perDayKey($ip), 86400);
    }

    private function perWindowKey(?string $ip): string
    {
        return "contact-strict-10min:{$ip}";
    }

    private function perDayKey(?string $ip): string
    {
        return "contact-strict-day:{$ip}";
    }

    private function hasFreshToken(Request $request): bool
    {
        $token = $request->input(self::TOKEN_FIELD);

        if (blank($token)) {
            return false;
        }

        try {
            $renderedAt = (int) Crypt::decrypt($token);
        } catch (DecryptException) {
            return false;
        }

        $elapsed = now()->timestamp - $renderedAt;

        return $elapsed >= self::MIN_SUBMIT_SECONDS && $elapsed <= self::MAX_TOKEN_AGE_SECONDS;
    }

    private function successResponse(): RedirectResponse
    {
        return back()->with('contact_success', 'Gửi liên hệ thành công, chúng tôi sẽ phản hồi sớm nhất.');
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
