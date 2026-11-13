<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Có tin nhắn liên hệ mới</title>
</head>
<body>
    <p>Có tin nhắn liên hệ mới từ website:</p>

    <ul>
        <li>Họ tên: {{ $contactMessage->name }}</li>
        <li>Số điện thoại: {{ $contactMessage->phone }}</li>
        <li>Email: {{ $contactMessage->email ?? 'Không có' }}</li>
        <li>Chủ đề: {{ $contactMessage->subject ?? 'Không có' }}</li>
    </ul>

    <p>Nội dung:</p>
    <p>{{ $contactMessage->message }}</p>
</body>
</html>
