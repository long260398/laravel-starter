<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dọn dữ liệu cá nhân tự động
    |--------------------------------------------------------------------------
    |
    | Số tháng giữ lại tin liên hệ đã xử lý (status = processed) trước khi bị
    | xóa hẳn bởi `php artisan model:prune`. Tin "Mới" hoặc "Đang xử lý" không
    | bao giờ bị xóa tự động.
    |
    */

    'prune_after_months' => (int) env('CONTACT_PRUNE_AFTER_MONTHS', 12),

];
