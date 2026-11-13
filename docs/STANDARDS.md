# Laravel Starter — Quy chuẩn bắt buộc

## Mục tiêu
Starter kit dùng chung cho website doanh nghiệp nhỏ (spa, F&B, giáo dục...).
Code phải chung chung, không gắn với một ngành cụ thể. Không over-engineering.

## Stack
Laravel 13, Filament 5, PHP >= 8.3 (máy dev chạy 8.5 nhưng code phải tương thích 8.3), SQLite (dev) / MySQL (prod).

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

## Giao diện
- Admin dùng tiếng Việt (APP_LOCALE=vi); label, thông báo, tên menu viết tiếng Việt.

## Cách làm việc
- Trước khi code: trình bày kế hoạch ngắn, chờ tôi duyệt.
- Test dùng PHPUnit (class-style, `php artisan make:test --phpunit`). Mỗi tính năng có feature test cho tạo/sửa/xóa + kiểm tra quyền.
- Chạy `php artisan test` và `vendor/bin/pint` trước khi báo xong.
- Không tự commit; tôi review rồi tự commit.