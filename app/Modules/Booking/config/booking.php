<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Khung giờ
    |--------------------------------------------------------------------------
    |
    | Khoá lưu trong DB (`bookings.time_slot`) — đổi nhãn ở đây không ảnh
    | hưởng lịch đã đặt (nhãn hiển thị được chụp lại vào `time_slot_label`
    | lúc khách gửi yêu cầu). Đây là quy tắc cấu trúc, đổi cần hiểu rõ ảnh
    | hưởng tới dữ liệu đã lưu nên để ở config (sửa qua code/deploy), không
    | đưa vào trang Cài đặt — giống cách `config/contact.php` giữ
    | `prune_after_months` ở config thay vì Settings.
    |
    */
    'time_slots' => [
        'morning' => 'Buổi sáng',
        'afternoon' => 'Buổi chiều',
        'evening' => 'Buổi tối',
    ],

    /*
    |--------------------------------------------------------------------------
    | Số ngày được đặt trước tối đa
    |--------------------------------------------------------------------------
    */
    'max_advance_days' => (int) env('BOOKING_MAX_ADVANCE_DAYS', 60),

    /*
    |--------------------------------------------------------------------------
    | Hiện ô email trên form
    |--------------------------------------------------------------------------
    */
    'show_email' => (bool) env('BOOKING_SHOW_EMAIL', true),

    /*
    |--------------------------------------------------------------------------
    | Dọn dữ liệu cá nhân tự động
    |--------------------------------------------------------------------------
    |
    | Số tháng giữ lại lịch đặt (tính theo `preferred_date`, mọi trạng thái)
    | trước khi bị xóa hẳn bởi `php artisan model:prune`.
    |
    */
    'prune_after_months' => (int) env('BOOKING_PRUNE_AFTER_MONTHS', 12),

];
