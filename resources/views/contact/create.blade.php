@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Liên hệ - '.setting('site_name', config('app.name'));
    @endphp

    <section class="mx-auto max-w-2xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-ink">Liên hệ</h1>

        @if (session('contact_success'))
            <p class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('contact_success') }}
            </p>
        @endif

        @if ($errors->any())
            <ul class="mt-4 list-inside list-disc rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-ink">Họ tên</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                    class="mt-1 block w-full rounded-md border border-brand-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-ink">Số điện thoại</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                    class="mt-1 block w-full rounded-md border border-brand-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-ink">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="mt-1 block w-full rounded-md border border-brand-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-ink">Chủ đề</label>
                <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
                    class="mt-1 block w-full rounded-md border border-brand-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-ink">Nội dung</label>
                <textarea id="message" name="message" rows="5"
                    class="mt-1 block w-full rounded-md border border-brand-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">{{ old('message') }}</textarea>
            </div>

            {{-- Honeypot: trường bẫy bot, phải để trống. Ẩn bằng CSS (không dùng type="hidden") để các trình
            đọc tự động điền form vẫn "thấy" field và mắc bẫy, nhưng người dùng thật không nhìn/focus tới. --}}
            <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
                <label for="{{ $honeypotField }}">Website</label>
                <input type="text" id="{{ $honeypotField }}" name="{{ $honeypotField }}" tabindex="-1" autocomplete="off">
            </div>

            <input type="hidden" name="{{ $tokenField }}" value="{{ $formToken }}">

            <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-surface">
                Gửi liên hệ
            </button>
        </form>
    </section>
@endsection
