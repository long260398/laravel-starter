@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Đặt lịch - '.setting('site_name', config('app.name'));
    @endphp

    <section class="mx-auto max-w-2xl px-4 py-14">
        <p class="text-sm font-medium uppercase tracking-wide text-earth">Đặt lịch</p>
        <h1 class="mt-3 font-serif text-3xl font-semibold text-ink">Đặt một khoảng lặng cho riêng bạn</h1>

        @if (session('booking_success'))
            <p class="mt-4 rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-sm text-brand-dark">
                {{ session('booking_success') }}
            </p>
        @endif

        @if ($errors->any())
            <ul class="mt-4 list-inside list-disc rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('booking.store') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-ink">Họ tên</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-ink">Số điện thoại</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required
                    class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">
            </div>

            @if ($showEmailField)
                <div>
                    <label for="email" class="block text-sm font-medium text-ink">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">
                </div>
            @endif

            @if (! empty($bookableSources))
                <div>
                    <label for="bookable_item" class="block text-sm font-medium text-ink">Dịch vụ</label>
                    <select id="bookable_item" name="bookable_item"
                        class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">
                        <option value="">— Không chọn —</option>
                        @foreach ($bookableSources as $sourceKey => $group)
                            <optgroup label="{{ $group['label'] }}">
                                @foreach ($group['items'] as $option)
                                    @php $value = "{$sourceKey}:{$option['slug']}"; @endphp
                                    <option value="{{ $value }}" @selected(old('bookable_item', $prefill ? "{$prefill['source']}:{$prefill['item']}" : null) === $value)>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="preferred_date" class="block text-sm font-medium text-ink">Ngày mong muốn</label>
                    <input type="date" id="preferred_date" name="preferred_date" value="{{ old('preferred_date') }}"
                        min="{{ $minDate }}" max="{{ $maxDate }}" required
                        class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">
                </div>

                <div>
                    <label for="time_slot" class="block text-sm font-medium text-ink">Khung giờ</label>
                    <select id="time_slot" name="time_slot" required
                        class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">
                        <option value="">— Chọn khung giờ —</option>
                        @foreach ($timeSlots as $slotKey => $slotLabel)
                            <option value="{{ $slotKey }}" @selected(old('time_slot') === $slotKey)>{{ $slotLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="party_size" class="block text-sm font-medium text-ink">Số người</label>
                <input type="number" id="party_size" name="party_size" value="{{ old('party_size', 1) }}" min="1" max="10" required
                    class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">
            </div>

            <div>
                <label for="note" class="block text-sm font-medium text-ink">Ghi chú</label>
                <textarea id="note" name="note" rows="4"
                    class="mt-1 block w-full rounded-xl border border-line bg-card px-3 py-2 text-sm focus:border-brand focus:outline-none">{{ old('note') }}</textarea>
            </div>

            {{-- Honeypot: trường bẫy bot, phải để trống. Ẩn bằng CSS (không dùng type="hidden") để các trình
            đọc tự động điền form vẫn "thấy" field và mắc bẫy, nhưng người dùng thật không nhìn/focus tới. --}}
            <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
                <label for="{{ $honeypotField }}">Website</label>
                <input type="text" id="{{ $honeypotField }}" name="{{ $honeypotField }}" tabindex="-1" autocomplete="off">
            </div>

            <input type="hidden" name="{{ $tokenField }}" value="{{ $formToken }}">

            <button type="submit" class="rounded-full bg-brand px-6 py-2.5 text-sm font-medium text-white transition hover:bg-brand-dark">
                Gửi yêu cầu đặt lịch
            </button>
        </form>
    </section>
@endsection
