<?php

namespace App\Http\Controllers;

use App\Actions\SubmitContactMessage;
use App\Exceptions\ContactRateLimitExceeded;
use App\Http\Controllers\Concerns\GuardsAntiSpamForm;
use App\Http\Requests\StoreContactMessageRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactMessageController extends Controller
{
    use GuardsAntiSpamForm;

    public function create(): View
    {
        return view('contact.create', [
            'honeypotField' => $this->honeypotField(),
            'tokenField' => $this->tokenField(),
            'formToken' => $this->makeAntiSpamToken(),
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        if ($this->isHoneypotFilled($request)) {
            return $this->successResponse();
        }

        if (! $this->hasFreshAntiSpamToken($request)) {
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

    private function successResponse(): RedirectResponse
    {
        return back()->with('contact_success', 'Gửi liên hệ thành công, chúng tôi sẽ phản hồi sớm nhất.');
    }
}
