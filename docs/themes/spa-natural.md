# Theme "spa-natural" — Design Brief

Theme thứ hai (sau `default`) cho starter kit — áp dụng cho khách hàng spa/thư
giãn. Tham chiếu phác thảo "Hướng A": thiên nhiên, thư thái, ấm, chậm rãi,
nhiều khoảng trắng.

## 1. Token màu (Tailwind v4 `@theme`, hex trực tiếp — không cần oklch)

| Token | Hex | Dùng cho |
|---|---|---|
| `--color-surface` | `#F6F3EC` | Nền chính (linen) |
| `--color-surface-alt` | `#ECE6DA` | Nền section xen kẽ (testimonials) |
| `--color-card` | `#FFFFFF` | Nền card/box nổi trên surface |
| `--color-line` | `#E2DCCF` | Viền mảnh thay bóng đổ |
| `--color-ink` | `#2B3328` | Chữ chính |
| `--color-ink-muted` | `#4A5246` | Chữ phụ |
| `--color-brand` | `#4F6651` | Nút chính, eyebrow nhấn — chữ trắng trên nền này |
| `--color-brand-dark` | `#3F5341` | Hover của `brand` |
| `--color-earth` | `#6B5444` | Băng ưu đãi, nút phụ, eyebrow — chữ trắng trên nền này |
| `--color-earth-dark` | `#5A4536` | Hover của `earth` |
| `--color-footer` | `#2B3328` | Nền footer |
| `--color-footer-ink` | `#E9E6DD` | Chữ trên footer |

Kiểm tra tương phản (WCAG, công thức luminance chuẩn):
- Trắng / `brand`: **6.3:1**
- Trắng / `earth`: **7.1:1**
- `ink` / `surface`: **11.8:1**
- `ink-muted` / `surface`: **7.3:1**
- `footer-ink` / `footer`: **10.5:1**

Tất cả ≥ 4.5:1 — đạt yêu cầu STANDARDS.

## 2. Font — self-host qua npm, không CDN

- `@fontsource/lora` — serif tiêu đề. Weight 500, 600 + italic 500. Chỉ import
  file subset `latin-*` và `vietnamese-*` (không import file tổng hợp `*.css`
  gồm mọi subset khác không dùng tới: cyrillic, greek...).
- `@fontsource/be-vietnam-pro` — sans nội dung. Weight 400, 500, 600, subset
  `latin-*` + `vietnamese-*`.
- `--font-serif: 'Lora', ...` và `--font-sans: 'Be Vietnam Pro', ...` khai báo
  trong `@theme` — tận dụng utility `font-serif`/`font-sans` có sẵn của
  Tailwind, không cần class mới.
- 2 package trên đã được duyệt (xem yêu cầu). Chạy `npm audit` sau khi thêm.

## 3. Hình khối

- Nút: `rounded-full` (999px) — token Tailwind có sẵn, không cần thêm.
- Card/ảnh: bo `rounded-3xl` (24px, token Tailwind có sẵn — đúng khoảng
  20–24px yêu cầu).
- Hero image: "vòm" — bo tròn hẳn 2 góc trên bằng 1 utility nhỏ
  `.arch-top` (`border-radius: clamp(64px, 10vw, 160px) clamp(64px, 10vw, 160px) 1.5rem 1.5rem`)
  định nghĩa trong `css/app.css` của theme.
- Thay bóng đổ bằng `border border-line` (viền 1px màu `line`) ở mọi
  card/section cần phân tách.

## 4. Ảnh thiếu dữ liệu

Chưa có ảnh thật (banner/service/album/testimonial đều chưa upload ảnh ở giai
đoạn demo) → mọi nơi hiển thị ảnh phải tự kiểm tra rồi fallback sang khối màu
tint (`bg-surface-alt` hoặc `bg-brand/10`, viền `border-line`) kèm nhãn mô tả
nhỏ (ví dụ "Ảnh dịch vụ", "Ảnh không gian") — không để `<img>` vỡ, không dùng
placeholder chung "No image".

## 5. Bố cục

### Header (`layouts/header.blade.php`)
Logo trái — menu giữa (ẩn trên mobile, dùng JS menu gập có sẵn của theme
`default`, copy nguyên `data-mobile-menu-toggle`/`data-mobile-menu`) — nút
"Đặt lịch qua Zalo" (`rounded-full bg-brand`) phải, chỉ hiện khi có
`setting('zalo_url')`.

### Footer (`layouts/footer.blade.php`)
Nền `footer`/`footer-ink`. Tên site, địa chỉ, giờ mở cửa, hotline/Zalo/
Facebook — mỗi khối tự ẩn khi thiếu setting tương ứng (giữ nguyên logic
`$hasContactInfo`/`$socialLinks` của theme `default`).

### Trang chủ (`home.blade.php`)
1. **Hero** 2 cột, chữ trái: eyebrow (lấy từ tên nhóm dịch vụ nổi bật nếu có,
   không query thêm — dùng dữ liệu banner), h1 serif lớn, mô tả, 2 nút (Zalo
   chính / Gọi phụ), dải giờ mở cửa + địa chỉ. Ảnh vòm bên phải.
   Dữ liệu: banner đầu tiên trong `$sliderBanners` (title → h1, subtitle →
   eyebrow, image, button_text/link_url → nút chính) — không có banner nào →
   fallback `setting('site_name')`/`setting('slogan')`, nút Zalo/hotline lấy
   từ Settings, không có ảnh → khối tint.
2. **Băng ưu đãi** nền `earth`, từ `$middleBanners->first()` — ẩn nếu rỗng.
3. Section module theo registry (`module_registry()->homeSections()`) — giữ
   nguyên vòng lặp của `default`, style kế thừa từ override view module.
4. **Tin tức mới nhất** — lưới 3 cột như `default`, màu/font theo theme.

### Override view module (đúng hợp đồng đã khai báo)
- `services::home-section` — "thực đơn": 2 cột (giới thiệu trái — danh sách
  phải). Mỗi dòng dịch vụ: tên (serif) — đường chấm leader — `price_label`
  (hoặc "Liên hệ" khi không có giá), dưới tên là mô tả ngắn + thời lượng. Kẻ
  dòng mảnh `border-line` giữa các dòng, không dùng lưới card.
- `gallery::home-section` — lưới không đều dùng chính `cover_image` của tối
  đa 3 album trong `$albums` (data hợp đồng chỉ có `albums`, không có ảnh con
  — không thể lấy nhiều ảnh trong 1 album mà không query thêm): album đầu
  tiên = ảnh lớn trái, 2 album kế = ảnh nhỏ xếp dọc phải.
- `testimonials::home-section` — nền `surface-alt`, tiêu đề trái, 2–3 trích
  dẫn serif nghiêng (`font-serif italic`), layout hàng ngang trên desktop.
- `faq::index` — accordion bằng `<details>`/`<summary>` kẻ dòng mảnh, giữ
  nguyên JSON-LD `$faqSchema` của controller.

### Blog / trang tĩnh / liên hệ / lỗi
`posts/index`, `posts/show`, `static-pages/show`, `contact/create`,
`errors/404`, `errors/500`, `components/banner-slider`, `components/post-card`
— override toàn bộ để đồng bộ màu/font (nếu không override sẽ fallback theme
`default` màu xanh dương, sai hệ màu). Cột đọc bài viết/trang tĩnh giới hạn
`max-w-[720px]`, `leading-[1.7]`.

## 6. Chuyển động

- 1 class `.reveal` (opacity 0 + translate-y nhẹ → opacity 1 + translate-y 0,
  300–500ms, CSS transition) gắn qua `data-reveal` trên section/card chính.
  1 đoạn JS nhỏ trong `js/app.js` của theme dùng `IntersectionObserver` để
  thêm class `is-visible` khi phần tử vào viewport.
- `@media (prefers-reduced-motion: reduce)` tắt hẳn transition/animation.
- Không áp `.reveal` lên form liên hệ.

## 7. Cấm (nhắc lại từ brief)

Không gradient tím/hồng, không glassmorphism, không emoji làm icon (icon =
SVG inline nét mảnh, `aria-hidden="true"`, nút icon có `aria-label` tiếng
Việt), không dùng Inter, không hero căn giữa, không lưới card đồng nhất suốt
trang, không lorem ipsum, không lẫn tiếng Anh.

## 8. Demo content

`DemoSpaSeeder` (chạy thủ công, tự chặn ngoài `local`/`testing`) tạo dữ liệu
mẫu cho spa giả "Hoa Mai Spa": Settings đầy đủ, 8 dịch vụ (2–3 nhóm), 2 banner
(`home_slider`, `home_middle`), 3 cảm nhận khách hàng, 5 FAQ, 3 bài viết. Ảnh
để trống — theme tự render khối tint.
