<?php

use App\Modules\Services\ServicesServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Module tùy chọn
    |--------------------------------------------------------------------------
    |
    | Mỗi module tự chứa model/migration/policy/Filament resource/route/view
    | trong app/Modules/{Tên}. Thêm module mới = tạo thư mục + thêm 1 entry ở
    | đây — không sửa bất kỳ file core nào (xem docs/STANDARDS.md).
    |
    | - enabled: bật/tắt qua .env. Tắt không xóa migration/dữ liệu đã có.
    | - provider: ServiceProvider của module, được ModulesServiceProvider
    |   đăng ký khi enabled = true.
    | - path: đường dẫn thư mục module (dùng cho Filament discoverResources).
    | - namespace: namespace PHP gốc của module (khớp với path).
    | - route_prefixes: segment đầu của route module — luôn bị cấm dùng làm
    |   slug StaticPage, bất kể module đang bật hay tắt.
    |
    */

    'services' => [
        'enabled' => (bool) env('MODULE_SERVICES', false),
        'provider' => ServicesServiceProvider::class,
        'path' => app_path('Modules/Services'),
        'namespace' => 'App\\Modules\\Services',
        'route_prefixes' => ['services'],
        'label' => 'Dịch vụ',
    ],

];
