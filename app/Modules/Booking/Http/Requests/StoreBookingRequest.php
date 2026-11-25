<?php

namespace App\Modules\Booking\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(0\d{9}|\+84\d{9})$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'bookable_item' => ['nullable', 'string', 'max:255'],
            'preferred_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.now()->toDateString(),
                'before_or_equal:'.now()->addDays((int) config('booking.max_advance_days'))->toDateString(),
            ],
            'time_slot' => ['required', Rule::in(array_keys(config('booking.time_slots')))],
            'party_size' => ['required', 'integer', 'min:1', 'max:10'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'email.email' => 'Email không hợp lệ.',
            'preferred_date.required' => 'Vui lòng chọn ngày mong muốn.',
            'preferred_date.after_or_equal' => 'Ngày mong muốn không được ở trong quá khứ.',
            'preferred_date.before_or_equal' => 'Ngày mong muốn ở quá xa, vui lòng chọn ngày gần hơn.',
            'time_slot.required' => 'Vui lòng chọn khung giờ.',
            'time_slot.in' => 'Khung giờ không hợp lệ.',
            'party_size.required' => 'Vui lòng nhập số người.',
            'party_size.min' => 'Số người tối thiểu là 1.',
            'party_size.max' => 'Số người tối đa là 10.',
        ];
    }
}
