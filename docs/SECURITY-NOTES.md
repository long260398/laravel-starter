# Sổ rủi ro đã biết, chấp nhận tạm thời

Lỗ hổng từ `npm audit`/`composer audit` **không có bản vá thực tế** tại thời
điểm kiểm tra, hoặc bản vá duy nhất đòi hỏi đánh đổi không chấp nhận được
(hạ cấp dependency chính, phá tương thích) thì ghi vào đây thay vì bỏ qua
lặng lẽ — xem `docs/STANDARDS.md` mục Bảo mật.

Mỗi lần chạy `npm audit` / `composer audit` phải đối chiếu kết quả mới với
sổ này: còn khớp với mục đã ghi → không cần hành động thêm; khác (advisory
mới, hoặc advisory cũ nay đã có fix) → cập nhật hoặc xoá mục.

## picomatch (qua `laravel-vite-plugin` → `vite-plugin-full-reload`, cũng qua `vite`/`tinyglobby`)

- **Advisory**: GHSA-fc5q-39xc-vjpf / CVE-2026-112366 — ReDoS qua glob pattern
  dạng extglob lặp một nhánh (vd `+(\*)`).
- **Ngày kiểm tra**: 2026-11-18.
- **Mức độ**: High (CVSS 7.5) theo advisory, nhưng advisory tính cho trường
  hợp tổng quát (service nhận glob pattern từ network).
- **Tình trạng vá**: Chưa có — advisory ghi rõ "Patched versions: None",
  phạm vi ảnh hưởng `<=4.0.7` bao trùm toàn bộ phiên bản picomatch từng phát
  hành (bản mới nhất cũng dính). `laravel-vite-plugin` (3.2.1) và
  `vite-plugin-full-reload` (1.2.0) đã là bản mới nhất, không có nâng cấp nào
  giúp thoát chuỗi phụ thuộc này. Repo picomatch có >15 PR đề xuất fix nhưng
  chưa cái nào được merge (xem issue #247) — không dùng branch chưa merge vì
  rủi ro supply-chain cao hơn chính lỗ hổng.
- **Vì sao rủi ro thấp trong dự án này**: picomatch chỉ được gọi lúc
  build/dev (`vite build`, `vite dev`) để khớp glob đọc từ `vite.config.js`
  do chính dự án viết — không có đường dẫn nào nhận glob pattern từ người
  dùng cuối hoặc request HTTP. Package này không được đóng gói vào bundle
  chạy ở browser hay vào runtime Laravel/PHP.
- **Biện pháp giảm thiểu**: KHÔNG mở Vite dev server ra mạng ngoài máy dev
  (không dùng `npm run dev -- --host`, không chạy `vite`/`vite dev` trên máy
  chủ production — production chỉ dùng artefact đã `npm run build`).
- **Khi nào xem lại**: picomatch phát hành bản vá (theo dõi
  https://github.com/micromatch/picomatch/issues/247), hoặc `laravel-vite-plugin`
  đổi sang dependency khác.
