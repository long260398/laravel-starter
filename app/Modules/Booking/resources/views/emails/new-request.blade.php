<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Có yêu cầu đặt lịch mới</title>
</head>
<body>
    <p>Có yêu cầu đặt lịch mới từ website:</p>

    <ul>
        <li>Họ tên: {{ $booking->name }}</li>
        <li>Số điện thoại: {{ $booking->phone }}</li>
        <li>Email: {{ $booking->email ?? 'Không có' }}</li>
        <li>Dịch vụ: {{ $booking->service_name ?? 'Không chọn' }}</li>
        <li>Ngày mong muốn: {{ $booking->preferred_date->format('d/m/Y') }}</li>
        <li>Khung giờ: {{ $booking->time_slot_label }}</li>
        <li>Số người: {{ $booking->party_size }}</li>
    </ul>

    @if ($booking->note)
        <p>Ghi chú:</p>
        <p>{{ $booking->note }}</p>
    @endif
</body>
</html>
