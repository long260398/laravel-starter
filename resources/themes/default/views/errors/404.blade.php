<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Không tìm thấy trang</title>
    @vite([active_theme()->cssEntry()])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-surface px-4 text-center font-sans text-ink">
    <p class="text-sm font-medium text-brand-600">404</p>
    <h1 class="mt-2 text-2xl font-semibold">Không tìm thấy trang</h1>
    <p class="mt-2 text-sm text-ink-muted">Trang bạn tìm không tồn tại hoặc đã bị gỡ bỏ.</p>
    <a href="{{ url('/') }}" class="mt-6 rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-surface">
        Về trang chủ
    </a>
</body>
</html>
