# Hợp đồng view của theme

Theme sống ở `resources/themes/{tên}/`. Một theme **chỉ được** chứa đúng các
view liệt kê ở đây (mục 1) và/hoặc view override của module đang bật (mục 2)
— `ThemeContractTest` fail nếu theme có file `.blade.php` nào khác trong
`views/`.

Mọi view chỉ được dùng đúng các biến liệt kê bên dưới — không được tự
`Model::query()`/Eloquent builder lấy thêm dữ liệu (xem `docs/STANDARDS.md`
mục "Theme frontend"). View được phép gọi accessor/helper thuần trên model đã
nhận qua biến (`$post->safe_content`, `Banner::isSafeLinkUrl()`...).

Theme `default` (`resources/themes/default/`) phải có đủ mọi view ở mục 1.
Theme khác chỉ cần có view nào muốn ghi đè — thiếu thì tự fallback về
`default`.

## 1. View bắt buộc (core)

| View (tên Laravel) | File trong `views/` | Biến nhận được |
|---|---|---|
| `layouts.app` | `layouts/app.blade.php` | Không nhận qua mảng `view()`. Đọc biến PHP tùy chọn do view con khai báo trước `@include('partials.seo', ...)`: `$seoTitle`, `$seoDescription`, `$ogImageUrl`, `$ogType`, `$canonicalUrl`, `$noindex` (tất cả optional, dùng `??`). `@include('layouts.header')`, `@include('layouts.footer')`, `@include('partials.seo', ...)`, `@include('partials.tracking')` — 2 partial sau là **core**, theme không override được. Phải gọi `@vite([active_theme()->cssEntry(), active_theme()->jsEntry()])`. **Bắt buộc** render slot `body_end` trước `</body>`: `@foreach (module_registry()->layoutPartials('body_end') as $item) @include($item['view'], $item['data']) @endforeach` — thiếu đoạn này, module nào cắm vào slot `body_end` (ví dụ `floating_contact`) sẽ không hiện trên theme đó. `ThemeContractTest` kiểm tra bằng cách bật module `floating_contact`, set setting mẫu rồi assert nút nổi xuất hiện khi render `layouts.app` của mọi theme. |
| `layouts.header` | `layouts/header.blade.php` | `$navStaticPages` — `Collection<StaticPage>`, do View Composer core cung cấp (`AppServiceProvider`), **không phải theme tự query**. Ngoài ra dùng `setting()`, `module_registry()->navItems()`. |
| `layouts.footer` | `layouts/footer.blade.php` | Không có — toàn bộ qua `setting()`. |
| `home` | `home.blade.php` | `$sliderBanners` (`Collection<Banner>`), `$middleBanners` (`Collection<Banner>`), `$latestPosts` (`Collection<Post>`). |
| `posts.index` | `posts/index.blade.php` | `$posts` (`LengthAwarePaginator<Post>`). |
| `posts.show` | `posts/show.blade.php` | `$post` (`Post`, với `category`/`tags` đã eager-load). `$tocHtml` (string, nội dung đã chèn id lên heading — **dùng thay cho `$post->safe_content` khi render nội dung**). `$tocItems` (`array<int, array{level: int, text: string, id: string}>`, rỗng = ẩn mục lục). `$relatedPosts` (`Collection<Post>`). `$articleJsonLd`, `$breadcrumbJsonLd` (mảng PHP thuần, render qua `@json(...)` trong `<script type="application/ld+json">`, không tự nối chuỗi). Thời gian đọc gọi trực tiếp qua accessor `$post->reading_time_minutes`, không truyền riêng. |
| `posts.category` | `posts/category.blade.php` | `$category` (`PostCategory`), `$posts` (`LengthAwarePaginator<Post>`). |
| `posts.tag` | `posts/tag.blade.php` | `$tag` (`Tag`), `$posts` (`LengthAwarePaginator<Post>`), `$noindex` (bool — true khi tổng số bài < 3). |
| `posts.search` | `posts/search.blade.php` | `$query` (string, **phải thoát bằng `{{ }}`, không bao giờ `{!! !!}`** — nguyên văn từ khoá người dùng nhập), `$posts` (`LengthAwarePaginator<Post>` hoặc `null` nếu từ khoá dưới 2 ký tự), `$noindex` (luôn `true`). |
| `static-pages.show` | `static-pages/show.blade.php` | `$page` (`StaticPage`). |
| `contact.create` | `contact/create.blade.php` | `$honeypotField` (string), `$tokenField` (string), `$formToken` (string). Ngoài ra dùng `$errors` (`ViewErrorBag`) — framework tự share qua session middleware khi chạy HTTP thật, không phải do controller truyền. |
| `errors.404` (= `errors::404`) | `errors/404.blade.php` | Không có. Trang độc lập, không `@extends('layouts.app')`. Vẫn gọi `@vite([active_theme()->cssEntry()])`. |
| `errors.500` (= `errors::500`) | `errors/500.blade.php` | Không có. Giống `errors.404`. |
| `components.banner-slider` (`<x-banner-slider>`) | `components/banner-slider.blade.php` | Prop `banners` (`Collection<Banner>`). |
| `components.post-card` (`<x-post-card>`) | `components/post-card.blade.php` | Prop `post` (`Post`). |

## 2. View module (tùy chọn, chỉ override khi module đang bật)

Đặt tại `views/modules/{module-key}/...` — đường dẫn con khớp y hệt view gốc
của module. `{module-key}` là khóa trong `config('modules')` (`services`,
`gallery`...).

Danh sách view module nào override được, và biến mỗi view nhận, **không
hardcode ở đây hay ở core** — mỗi module tự khai báo "hợp đồng" của chính nó
bằng cách gọi `ModuleRegistry::registerThemeViewContract(callable $provider)`
trong `boot()` của `{Tên}ServiceProvider`. `$provider` là 1 callable không
nhận tham số, trả về mảng:

```php
[
    '{module-key}/{đường dẫn y hệt view gốc}.blade.php' => [
        '{logical-view}', // vd 'services::home-section'
        ['bien' => $giaTriMau], // dữ liệu mẫu để test render thử
    ],
    // ... có thể khai nhiều view trong 1 provider
]
```

Ví dụ (`ServicesServiceProvider::boot()`):

```php
$registry->registerThemeViewContract(function (): array {
    $category = ServiceCategory::factory()->published()->create();
    $service = Service::factory()->published()->for($category, 'category')->create();

    return [
        'services/home-section.blade.php' => ['services::home-section', [
            'services' => collect([$service]),
        ]],
        // ...
    ];
});
```

`ThemeContractTest` đọc lại toàn bộ qua `module_registry()->themeViewContracts()`
để biết: (a) view nào theme được phép override (`test_theme_does_not_ship_views_outside_the_contract`),
(b) dữ liệu mẫu nào dùng để render thử khi theme có override
(`test_module_view_overrides_render_with_sample_data_when_present`). Theme
override 1 view mà module chưa khai báo contract sẽ bị test coi là "view
ngoài hợp đồng" và fail — không cần core biết tên module nào tồn tại.

Dữ liệu mẫu được bọc trong Closure, chỉ chạy khi `ThemeContractTest` gọi
(giống cách `registerHomeSection`/`registerLayoutPartial` dùng Closure để lazy
— factory tạo bản ghi mẫu không chạy ở mọi request thật, chỉ chạy trong test).

Ví dụ tham khảo — view của module `booking` (`BookingServiceProvider::boot()`):

| View | File override | Biến nhận được |
|---|---|---|
| `booking::create` | `modules/booking/create.blade.php` | `$bookableSources` (`array<string, array{label: string, items: array<int, array{slug: string, label: string}>}>`, từ `module_registry()->bookableSources()`), `$prefill` (`array{source: string, item: string, label: string}\|null`), `$timeSlots` (`array<string, string>`), `$minDate`/`$maxDate` (string `Y-m-d`), `$showEmailField` (bool), `$honeypotField`/`$tokenField`/`$formToken` (string). Ngoài ra dùng `$errors`. |
| `booking::home-section` | `modules/booking/home-section.blade.php` | Không có — toàn bộ là nội dung/link tĩnh. |

### Slot mở rộng (`<x-module-slot>`)

Một số view (core hoặc module) tự mở "điểm cắm" cho module khác bằng
component chung `<x-module-slot name="..." :context="[...]" />` (thay cho
viết tay `@foreach (module_registry()->layoutPartials(...))`). `name` là tên
slot do chính view đó đặt (ví dụ Services đặt `service.show.actions` trên
trang chi tiết dịch vụ của nó); `context` là dữ liệu truyền tường minh cho
module cắm vào — module cắm vào chỉ được đọc đúng các khoá trong `context`,
không dựa vào biến khác của view gọi.

Nếu 1 view gốc có `<x-module-slot name="X">`, **mọi theme override view đó
cũng phải có đúng slot `X`** — thiếu là mất điểm cắm trên theme đó mà không
báo lỗi rõ ràng. `ThemeContractTest` tự so khớp tên slot giữa bản gốc và mọi
bản override (core lẫn module), không cần khai riêng.

## 3. Không thuộc theme (core, cố định)

- `partials/seo.blade.php`, `partials/tracking.blade.php` — core, theme
  không override.
- `sitemap.blade.php`, `robots.blade.php` — không có bản sắc hình ảnh, ở lại
  `resources/views/`.
- `resources/views/emails/` — email không thuộc theme.
- Filament admin (`app/Providers/Filament`, `resources/views/filament`) —
  không thuộc theme.

## 4. Vite / asset

- Mỗi theme có `css/app.css` + `js/app.js` riêng (`resources/themes/{tên}/`).
  `vite.config.js` tự quét thư mục `resources/themes`, thêm theme mới không
  cần sửa file này.
- `css/app.css` của theme phải `@source` đủ 3 nhóm để Tailwind quét ra class
  dùng trong các view fallback:
  - view của chính theme đó (`../views/**/*.blade.php`);
  - view của theme `default` (`../../default/views/**/*.blade.php`) — trừ khi
    chính nó là `default`;
  - view của module (`../../../../app/Modules/*/resources/views/**/*.blade.php`).
