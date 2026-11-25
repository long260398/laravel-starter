<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsAntiSpamForm;
use App\Http\Controllers\Controller;
use App\Modules\Booking\Actions\SubmitBookingRequest;
use App\Modules\Booking\Exceptions\BookingRateLimitExceeded;
use App\Modules\Booking\Http\Requests\StoreBookingRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use GuardsAntiSpamForm;

    public function create(Request $request): View
    {
        return view('booking::create', [
            'bookableSources' => module_registry()->bookableSources(),
            'prefill' => $this->resolvePrefill($request->string('source')->toString(), $request->string('item')->toString()),
            'timeSlots' => config('booking.time_slots'),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays((int) config('booking.max_advance_days'))->toDateString(),
            'showEmailField' => (bool) config('booking.show_email'),
            'honeypotField' => $this->honeypotField(),
            'tokenField' => $this->tokenField(),
            'formToken' => $this->makeAntiSpamToken(),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        if ($this->isHoneypotFilled($request)) {
            return $this->successResponse();
        }

        if (! $this->hasFreshAntiSpamToken($request)) {
            return back()->withErrors(['form' => 'Vui lòng thử lại.']);
        }

        $data = $request->safe()->only(['name', 'phone', 'email', 'preferred_date', 'time_slot', 'party_size', 'note']);

        $prefill = $this->resolvePrefillFromComposite($request->input('bookable_item'));

        $data['service_name'] = $prefill['label'] ?? null;
        $data['time_slot_label'] = config("booking.time_slots.{$data['time_slot']}");

        try {
            app(SubmitBookingRequest::class)->handle(
                $data,
                (string) $request->ip(),
                $request->userAgent() === null ? null : mb_substr($request->userAgent(), 0, 255),
            );
        } catch (BookingRateLimitExceeded $e) {
            abort(429, 'Bạn đã gửi yêu cầu đặt lịch quá nhiều lần, vui lòng thử lại sau.', [
                'Retry-After' => (string) $e->retryAfter,
            ]);
        }

        return $this->successResponse();
    }

    /**
     * Form chỉ có 1 ô chọn (gộp mọi nhóm nguồn), giá trị dạng
     * "{source}:{slug}" để không cần JS đồng bộ 2 ô riêng.
     *
     * @return array{source: string, item: string, label: string}|null
     */
    private function resolvePrefillFromComposite(?string $composite): ?array
    {
        if (blank($composite) || ! str_contains($composite, ':')) {
            return null;
        }

        [$source, $item] = explode(':', $composite, 2);

        return $this->resolvePrefill($source, $item);
    }

    /**
     * Tra `source`/`item` qua chính registry "nguồn đặt lịch được"
     * (`module_registry()->bookableSources()`) — không import class/kiểm tra
     * cờ module nào cụ thể. Slug không khớp (sai, bị xoá, chưa xuất bản khi
     * module tự lọc published) → coi như không có, không lỗi.
     *
     * @return array{source: string, item: string, label: string}|null
     */
    private function resolvePrefill(string $source, string $item): ?array
    {
        if ($source === '' || $item === '') {
            return null;
        }

        $sources = module_registry()->bookableSources();

        if (! isset($sources[$source])) {
            return null;
        }

        $match = collect($sources[$source]['items'])->firstWhere('slug', $item);

        if ($match === null) {
            return null;
        }

        return ['source' => $source, 'item' => $item, 'label' => $match['label']];
    }

    private function successResponse(): RedirectResponse
    {
        return back()->with('booking_success', 'Đã gửi yêu cầu đặt lịch. Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.');
    }
}
