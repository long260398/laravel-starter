<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Theme frontend
    |--------------------------------------------------------------------------
    |
    | Theme đang dùng cho frontend (không áp dụng cho Filament admin và email).
    | Đổi theme cho một khách hàng chỉ cần đổi APP_THEME trong .env, không sửa
    | controller. Theme không tồn tại (thư mục `{path}/{active}/views` không
    | có) sẽ rơi về `default` và ghi log cảnh báo — xem App\Support\Theme\
    | ActiveTheme.
    |
    */

    'active' => env('APP_THEME', 'default'),

    'default' => 'default',

    'path' => resource_path('themes'),

];
