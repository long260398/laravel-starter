<?php

namespace App\Http\Controllers;

use App\Actions\SubmitContactMessage;
use App\Exceptions\ContactRateLimitExceeded;
use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

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
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return $this->successResponse();
        }

        if (! $this->hasFreshToken($request)) {
            return back()->withErrors(['form' => 'Vui lòng thử lại.']);
        }

        try {
            app(SubmitContactMessage::class)->handle(
                $request->safe()->only(['name', 'phone', 'email', 'subject', 'message']),
                (string) $request->ip(),
                $request->userAgent() === null ? null : mb_substr($request->userAgent(), 0, 255),
            );
        } catch (ContactRateLimitExceeded $e) {
            abort(429, 'Bạn đã gửi liên hệ quá nhiều lần, vui lòng thử lại sau.', [
                'Retry-After' => (string) $e->retryAfter,
            ]);
        }

        return $this->successResponse();
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
}
