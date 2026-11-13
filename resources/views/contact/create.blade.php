<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Liên hệ</title>
</head>
<body>
    <h1>Liên hệ</h1>

    @if (session('contact_success'))
        <p>{{ session('contact_success') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('contact.store') }}">
        @csrf

        <div>
            <label for="name">Họ tên</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}">
        </div>

        <div>
            <label for="phone">Số điện thoại</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}">
        </div>

        <div>
            <label for="subject">Chủ đề</label>
            <input type="text" id="subject" name="subject" value="{{ old('subject') }}">
        </div>

        <div>
            <label for="message">Nội dung</label>
            <textarea id="message" name="message">{{ old('message') }}</textarea>
        </div>

        {{-- Honeypot: trường bẫy bot, phải để trống. Ẩn bằng CSS (không dùng type="hidden") để các trình
        đọc tự động điền form vẫn "thấy" field và mắc bẫy, nhưng người dùng thật không nhìn/focus tới. --}}
        <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
            <label for="{{ $honeypotField }}">Website</label>
            <input type="text" id="{{ $honeypotField }}" name="{{ $honeypotField }}" tabindex="-1" autocomplete="off">
        </div>

        <input type="hidden" name="{{ $tokenField }}" value="{{ $formToken }}">

        <button type="submit">Gửi liên hệ</button>
    </form>
</body>
</html>
