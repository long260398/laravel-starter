# Local changes

Nguồn: github.com/nutlope/hallmark @ v2.0.0, commit `04977e15f3dfd6ee4b74c887dac3eb52665d6c92` (clone ngày 2026-11-25).

## Các chỗ đã sửa so với bản gốc

- `references/flow.md` — mục "Pre-flight edge cases" / "The product lives somewhere else": bỏ "start it there (install only what its own lockfile asks for, on a free port)", thay bằng "Không tự cài package hay khởi động server; hỏi người dùng trước."
- `references/imagery.md` — § 4 "The product: captured first, built only when it cannot be": bỏ hướng dẫn chạy `capture.mjs --cdp ...` cho desktop app (Electron/WebView2/CEF), thay bằng "Không dùng `--cdp`."

## Không copy

- `agents/openai.yaml` (dành cho Codex) — không copy vào skill này.

## Cập nhật skill

Khi có bản Hallmark mới: copy bản mới từ `skills/hallmark` rồi sửa lại các chỗ trên (và cập nhật commit/version ở đầu file này).
