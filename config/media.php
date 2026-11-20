<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk lưu file media
    |--------------------------------------------------------------------------
    |
    | Disk dùng để lưu file upload (ảnh, tài liệu...) cho code mới — xem
    | docs/STANDARDS.md mục "Lưu trữ file". Đổi nơi lưu (R2, S3, FTP...) chỉ
    | cần đổi MEDIA_DISK trong .env, không sửa code.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

];
