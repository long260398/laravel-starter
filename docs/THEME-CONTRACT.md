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
| `layouts.app` | `layouts/app.blade.php` | Không nhận qua mảng `view()`. Đọc biến PHP tùy chọn do view con khai báo trước `@include('partials.seo', ...)`: `$seoTitle`, `$seoDescription`, `$ogImageUrl`, `$ogType`, `$canonicalUrl` (tất cả optional, dùng `??`). `@include('layouts.header')`, `@include('layouts.footer')`, `@include('partials.seo', ...)`, `@include('partials.tracking')` — 2 partial sau là **core**, theme không override được. Phải gọi `@vite([active_theme()->cssEntry(), active_theme()->jsEntry()])`. |
| `layouts.header` | `layouts/header.blade.php` | `$navStaticPages` — `Collection<StaticPage>`, do View Composer core cung cấp (`AppServiceProvider`), **không phải theme tự query**. Ngoài ra dùng `setting()`, `module_registry()->navItems()`. |
| `layouts.footer` | `layouts/footer.blade.php` | Không có — toàn bộ qua `setting()`. |
| `home` | `home.blade.php` | `$sliderBanners` (`Collection<Banner>`), `$middleBanners` (`Collection<Banner>`), `$latestPosts` (`Collection<Post>`). |
| `posts.index` | `posts/index.blade.php` | `$posts` (`LengthAwarePaginator<Post>`). |
| `posts.show` | `posts/show.blade.php` | `$post` (`Post`). |
| `static-pages.show` | `static-pages/show.blade.php` | `$page` (`StaticPage`). |
| `contact.create` | `contact/create.blade.php` | `$honeypotField` (string), `$tokenField` (string), `$formToken` (string). Ngoài ra dùng `$errors` (`ViewErrorBag`) — framework tự share qua session middleware khi chạy HTTP thật, không phải do controller truyền. |
| `errors.404` (= `errors::404`) | `errors/404.blade.php` | Không có. Trang độc lập, không `@extends('layouts.app')`. Vẫn gọi `@vite([active_theme()->cssEntry()])`. |
| `errors.500` (= `errors::500`) | `errors/500.blade.php` | Không có. Giống `errors.404`. |
| `components.banner-slider` (`<x-banner-slider>`) | `components/banner-slider.blade.php` | Prop `banners` (`Collection<Banner>`). |
| `components.post-card` (`<x-post-card>`) | `components/post-card.blade.php` | Prop `post` (`Post`). |

## 2. View module (tùy chọn, chỉ override khi module đang bật)

Đặt tại `views/modules/{module-key}/...` — đường dẫn con khớp y hệt view gốc
của module. `{module-key}` là khóa trong `config('modules')` (`services`,
`gallery`).

| View gốc | File override | Biến nhận được |
|---|---|---|
| `services::index` | `modules/services/index.blade.php` | `$categories` (`Collection<ServiceCategory>`), `$services` (`Collection<Service>`), `$activeCategory` (string). |
| `services::show` | `modules/services/show.blade.php` | `$service` (`Service`). |
| `services::nav-item` | `modules/services/nav-item.blade.php` | Không có. |
| `services::home-section` | `modules/services/home-section.blade.php` | `$services` (`Collection<Service>`). |
| `services::components.service-card` | `modules/services/components/service-card.blade.php` | `$service` (`Service`). |
| `gallery::index` | `modules/gallery/index.blade.php` | `$albums` (`Collection<GalleryAlbum>`). |
| `gallery::show` | `modules/gallery/show.blade.php` | `$album` (`GalleryAlbum`, đã `load('images')`). |
| `gallery::nav-item` | `modules/gallery/nav-item.blade.php` | Không có. |
| `gallery::home-section` | `modules/gallery/home-section.blade.php` | `$albums` (`Collection<GalleryAlbum>`). |
| `gallery::components.album-card` | `modules/gallery/components/album-card.blade.php` | `$album` (`GalleryAlbum`). |

Module mới thêm `route_prefixes`/entry trong `config/modules.php` thì bổ sung
luôn dòng tương ứng ở bảng này — không bắt buộc nhưng giúp
`ThemeContractTest` biết view nào hợp lệ để test khi có theme override.

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
