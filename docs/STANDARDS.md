# Laravel Starter — Quy chuẩn bắt buộc

## Mục tiêu
Starter kit dùng chung cho website doanh nghiệp nhỏ (spa, F&B, giáo dục...).
Code phải chung chung, không gắn với một ngành cụ thể. Không over-engineering.

## Stack
Laravel 13, Filament 5, PHP >= 8.3 (máy dev chạy 8.5 nhưng code phải tương thích 8.3), SQLite (dev) / MySQL (prod).
Timezone: Asia/Ho_Chi_Minh.

## Database
- PK: `id` BIGINT tự tăng. FK: `{entity}_id`, luôn có index + foreign key constraint.
- Tên bảng snake_case số nhiều, cột snake_case. Boolean: `is_`/`has_`.
- Bảng nội dung dùng `softDeletes()`. Không viết migration phá dữ liệu.

## Cấu trúc module
- Mỗi tính năng chung (Posts, Pages, Banners, Contacts, Settings) là một nhóm rõ ràng: model, migration, Filament resource, policy.
- Tính năng riêng theo ngành để sau, không thêm vào core.
- File upload: model dùng trait `DeletesStoredFiles` và khai báo `$fileColumns`.

## Phân quyền
- Role: Admin (toàn quyền), Editor (quản lý nội dung, không force delete, không quản lý User/Settings).
- Hệ thống không bao giờ được còn 0 Admin active (chặn ở model).
- Không bật đăng ký công khai cho admin panel. Admin tạo bằng `php artisan app:make-admin`.

## Bảo mật (bắt buộc)
- Mỗi model có Policy; Filament resource phải kiểm tra quyền.
- Luôn khai báo `$fillable`; không dùng `$guarded = []`.
- Validate mọi input; upload ảnh giới hạn MIME (jpg, png, webp) và dung lượng.
- HTML từ RichEditor: khi render ở Blade frontend luôn dùng `str($html)->sanitizeHtml()` (helper sẵn có của Filament). Cấm dùng `strip_tags()` để chống XSS.
- Form public (đăng ký, hồ sơ) không bao giờ fill trực tiếp vào User; chỉ lưu `validated()->only([...])` để chống tự nâng quyền.
- Seeder không bao giờ tạo tài khoản có mật khẩu mặc định ngoài môi trường local/testing.
- Không hardcode secret; không commit `.env`.
- Không tải thư viện JS qua CDN; mọi package qua composer/npm.
- Không thêm package mới khi chưa được duyệt; ưu tiên dùng thứ có sẵn trong Laravel/Filament.
- Thêm package mới phải chạy `composer audit` / `npm audit`.
- Khi deploy sau proxy/CDN (Cloudflare...) phải cấu hình `TrustProxies` để rate limit theo đúng IP khách.

## Module tùy chọn
Tính năng riêng theo ngành (Dịch vụ, Sản phẩm...) là module độc lập, bật/tắt qua `.env`, **không bao giờ xóa dữ liệu khi tắt**. Thêm module mới = tạo thư mục + thêm 1 entry trong `config/modules.php` — **không sửa bất kỳ file core nào**.

- **Cấu trúc**: `app/Modules/{Tên}/` chứa `Models/`, `Policies/`, `Http/Controllers/`, `Database/Migrations/`, `Filament/Resources/`, `routes/web.php`, `resources/views/`, và 1 `{Tên}ServiceProvider`. Factory của model trong module nằm TRONG module, ở `app/Modules/{Tên}/Database/Factories/` (namespace `App\Modules\{Tên}\Database\Factories`). Viết hoa `Database` (không phải `database`) để khớp đúng từng ký tự hoa/thường giữa namespace và tên thư mục vật lý — PSR-4 ánh xạ namespace sang đường dẫn case-sensitive, sai một ký tự hoa/thường là vỡ autoload trên Linux dù Windows không báo lỗi (Windows không phân biệt hoa/thường nên che mất lỗi này). `{Tên}ServiceProvider::boot()` gọi `loadMigrationsFrom(__DIR__.'/Database/Migrations')`. Model phải override `newFactory()` để chỉ đích danh (`HasFactory` không tự đoán được factory nằm ngoài `App\Models`).
- **Khai báo** trong `config/modules.php`: mỗi module gồm `enabled` (đọc từ env), `provider` (FQCN), `path`, `namespace`, `route_prefixes` (mảng segment đầu của route module), `label`.
- **Đăng ký provider**: `App\Providers\ModulesServiceProvider` (core, cố định) tự loop `config('modules')` và `$this->app->register()` provider nào có `enabled = true`. Provider của module **không cần tự kiểm tra lại cờ** — nó chỉ tồn tại trong request khi module bật.
- **Filament resource**: `AdminPanelProvider` loop qua module bật, `discoverResources(in: "{path}/Filament/Resources", for: "{namespace}\Filament\Resources")` — không hardcode tên module.
- **3 điểm cắm core↔module** qua singleton `App\Support\Modules\ModuleRegistry` (helper `module_registry()`):
  - `registerNavItem(string $view, array|Closure $data = [])` — thêm mục menu.
  - `registerHomeSection(string $view, array|Closure $data = [])` — thêm section trang chủ (`$data` dùng `Closure` để query lazy, chỉ chạy lúc render).
  - `registerSitemapUrls(callable $provider)` — thêm URL vào sitemap.
  Module tự gọi 3 hàm này trong `boot()`. Core chỉ `@include($item['view'], $item['data'])` (nav/home) hoặc `->merge(module_registry()->sitemapUrls())` (sitemap) — **không biết tên module nào**.
- **Module không truyền HTML thô vào core.** Mọi điểm cắm trao đổi qua `view + data` (hoặc mảng URL thuần cho sitemap), không bao giờ qua chuỗi HTML đã render/`{!! !!}` — core không audit được HTML tự do từ module.
- **Route của module** nạp qua `loadRoutesFrom()` trong `boot()` của module provider. Route "trang tĩnh" (`StaticPage`, slug tự do) đăng ký bằng `Route::fallback()` — Laravel luôn xét route fallback sau cùng, bất kể thứ tự đăng ký, nên route module không cần quan tâm thứ tự boot.
- **Slug cấm**: `StaticPage::isReservedSlug()` tự gộp `route_prefixes` từ `config('modules')` — áp dụng **cả khi module đang tắt** (để không bị đè khi bật lại sau). Thêm module mới chỉ cần khai `route_prefixes` trong config, không sửa `StaticPage.php`.
- **Vận hành**: bật module xong phải chạy `php artisan migrate` (migration của module không tự chạy nếu module tắt lúc `migrate`). Tắt module không xóa migration/bảng/dữ liệu đã có — chỉ dừng đăng ký route/view/policy/Filament resource/registry.
- **Test**: thêm module mới thì thêm biến `MODULE_xxx=true` vào `phpunit.xml` (mặc định bật cho cả suite, giống `MODULE_SERVICES`, `MODULE_GALLERY`). Chỉ test "module tắt" mới tự set env tắt riêng trong `setUp()`/khôi phục ở `tearDown()` (xem ví dụ `ServiceModuleDisabledTest`/`GalleryModuleDisabledTest`) — không tạo cơ chế bật module riêng cho test.

## Theme frontend
Frontend tách khỏi controller qua hệ thống theme — đổi giao diện cho một khách hàng chỉ cần đổi `APP_THEME`, không sửa controller/route. Admin (Filament) và email **không thuộc theme**.

- **Cấu trúc**: `resources/themes/{tên}/` gồm `views/`, `css/app.css`, `js/app.js`. Theme `default` là theme gốc, luôn phải có đủ view bắt buộc — xem `docs/THEME-CONTRACT.md`.
- **Resolve view**: theme đang active (`config('theme.active')`, đọc từ `APP_THEME`) được ưu tiên; view nào theme đó không có thì tự fallback về theme `default` — cơ chế do `App\Support\Theme\ThemeViewResolver` xử lý (prepend view path), controller/module gọi `view('home')`, `view('services::index')` như bình thường, không cần biết theme nào đang active. Theme không tồn tại (thiếu thư mục `views`) thì rơi về `default` + ghi log cảnh báo (`App\Support\Theme\ActiveTheme`).
- **Override view của module** (`services::`, `gallery::`...): đặt tại `resources/themes/{tên}/modules/{module}/...` (đường dẫn giống hệt view gốc của module, ví dụ `modules/services/index.blade.php` ghi đè `services::index`). Thiếu thì dùng view gốc của module.
- **Theme CHỈ được override đúng danh sách view trong `docs/THEME-CONTRACT.md` (và view của module)** — không được thêm view khác vào `resources/themes/{tên}/views/`. Partials dùng chung (`partials.seo`, `partials.tracking`), `sitemap.blade.php`, `robots.blade.php`, view email (`resources/views/emails`) là core, theme không được ghi đè. `ThemeContractTest` fail nếu theme có file view ngoài danh sách cho phép.
- **View của theme không được tự `Model::query()`/Eloquent builder để lấy dữ liệu.** Mọi dữ liệu phải do controller, view composer (`View::composer`), hoặc helper `setting()` truyền vào qua biến — xem biến mỗi view nhận ở `docs/THEME-CONTRACT.md`. Theme được phép gọi accessor/helper thuần (không query DB) trên model đã nhận qua biến, ví dụ `$post->safe_content`, `Banner::isSafeLinkUrl()`.
- **Asset**: mỗi theme có entry Vite riêng (`css/app.css`, `js/app.js`), `vite.config.js` tự quét `resources/themes/*` — thêm theme mới không cần sửa `vite.config.js`. CSS theme phải `@source` cả view của chính nó, view của theme `default` (vì fallback) và view của module (`app/Modules/*/resources/views`) để Tailwind quét đủ class dùng trong các view fallback.
- **Test**: thêm theme mới không cần sửa test core; `ThemeContractTest` tự quét mọi theme trong `resources/themes/`, render từng view hợp đồng mà theme đó có bằng dữ liệu mẫu đúng `docs/THEME-CONTRACT.md` — view dùng biến ngoài hợp đồng sẽ fail rõ theme/view nào.

## Giao diện
- Admin dùng tiếng Việt (APP_LOCALE=vi); label, thông báo, tên menu viết tiếng Việt.
- Mọi Filament resource (core và module) phải đặt `protected static bool $hasTitleCaseModelLabel = false;` — Filament mặc định viết hoa từng chữ (`Str::ucwords()`) label cho breadcrumb/modal, làm sai label tiếng Việt (ví dụ "Bài Viết" thay vì "Bài viết").
- Mọi Filament resource/page (core và module) phải đặt `$navigationGroup` bằng enum `App\Enums\NavigationGroup` (`Content`, `Customers`, `System`), không gõ chuỗi tên nhóm trực tiếp — gõ sai chuỗi sẽ sinh ra 2 nhóm navigation trùng nghĩa khác tên. Thứ tự nhóm hiển thị khai báo một lần ở `AdminPanelProvider::navigationGroups()`.

## Routing / URL
- Route cố định do hệ thống định nghĩa (không phải slug nội dung do người dùng nhập) dùng tiếng Anh: `/blog`, `/contact`, `/sitemap.xml`, `/robots.txt`...
- Ngoại lệ: slug của nội dung do editor/khách tạo — bài viết (Post), trang tĩnh (StaticPage), sản phẩm (Product, sau này) — giữ tự do theo tiêu đề gốc (có thể tiếng Việt có dấu/không dấu), không ép tiếng Anh.

## Kiến trúc & API
- Logic nghiệp vụ đặt trong Actions (`app/Actions`, module thì `app/Modules/{Tên}/Actions`): mỗi action 1 class, 1 method `handle()`. Controller, Filament và API (sau này) chỉ gọi lại, không viết logic trùng.
- Áp dụng cho code mới. Code cũ (Contact, Settings...) sẽ được tách trong task riêng, không tự refactor khi đang làm task khác.
- Chưa làm API. Khi cần, API sẽ là module `app/Modules/Api`, mặc định tắt.

## Cách làm việc
- Trước khi code: trình bày kế hoạch ngắn, chờ tôi duyệt.
- Test dùng PHPUnit (class-style, `php artisan make:test --phpunit`). Mỗi tính năng có feature test cho tạo/sửa/xóa + kiểm tra quyền.
- Chạy `php artisan test` và `vendor/bin/pint` trước khi báo xong.
- Không tự commit; tôi review rồi tự commit.