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

## Bảo mật (bắt buộc)
- Mỗi model có Policy; Filament resource phải kiểm tra quyền.
- Luôn khai báo `$fillable`; không dùng `$guarded = []`.
- Validate mọi input; upload ảnh giới hạn MIME (jpg, png, webp) và dung lượng.
- Nội dung HTML từ editor phải được sanitize trước khi render ra frontend.
- Không hardcode secret; không commit `.env`.
- Không tải thư viện JS qua CDN; mọi package qua composer/npm.
- Thêm package mới phải chạy `composer audit` / `npm audit`.

## Cách làm việc
- Trước khi code: trình bày kế hoạch ngắn, chờ tôi duyệt.
- Mỗi tính năng phải có PHPUnit feature test (ít nhất: tạo/sửa/xóa + kiểm tra quyền).
- Chạy `php artisan test` và `vendor/bin/pint` trước khi báo xong.
- Không tự commit; tôi review rồi tự commit.