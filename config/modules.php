<?php

use App\Modules\Booking\BookingServiceProvider;
use App\Modules\Faq\FaqServiceProvider;
use App\Modules\FloatingContact\FloatingContactServiceProvider;
use App\Modules\Gallery\GalleryServiceProvider;
use App\Modules\Services\ServicesServiceProvider;
use App\Modules\Testimonials\TestimonialsServiceProvider;

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

    'gallery' => [
        'enabled' => (bool) env('MODULE_GALLERY', false),
        'provider' => GalleryServiceProvider::class,
        'path' => app_path('Modules/Gallery'),
        'namespace' => 'App\\Modules\\Gallery',
        'route_prefixes' => ['gallery'],
        'label' => 'Thư viện ảnh',
    ],

    'floating_contact' => [
        'enabled' => (bool) env('MODULE_FLOATING_CONTACT', false),
        'provider' => FloatingContactServiceProvider::class,
        'path' => app_path('Modules/FloatingContact'),
        'namespace' => 'App\\Modules\\FloatingContact',
        'route_prefixes' => [],
        'label' => 'Nút liên hệ nổi',
    ],

    'testimonials' => [
        'enabled' => (bool) env('MODULE_TESTIMONIALS', false),
        'provider' => TestimonialsServiceProvider::class,
        'path' => app_path('Modules/Testimonials'),
        'namespace' => 'App\\Modules\\Testimonials',
        'route_prefixes' => [],
        'label' => 'Cảm nhận khách hàng',
    ],

    'faq' => [
        'enabled' => (bool) env('MODULE_FAQ', false),
        'provider' => FaqServiceProvider::class,
        'path' => app_path('Modules/Faq'),
        'namespace' => 'App\\Modules\\Faq',
        'route_prefixes' => ['faq'],
        'label' => 'Câu hỏi thường gặp',
    ],

    'booking' => [
        'enabled' => (bool) env('MODULE_BOOKING', false),
        'provider' => BookingServiceProvider::class,
        'path' => app_path('Modules/Booking'),
        'namespace' => 'App\\Modules\\Booking',
        'route_prefixes' => ['booking'],
        'label' => 'Đặt lịch',
    ],

];
