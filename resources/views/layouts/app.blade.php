<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('partials.seo', [
        'seoTitle' => $seoTitle ?? null,
        'seoDescription' => $seoDescription ?? null,
        'ogImageUrl' => $ogImageUrl ?? null,
        'ogType' => $ogType ?? null,
        'canonicalUrl' => $canonicalUrl ?? null,
    ])

    @if (setting('favicon'))
        <link rel="icon" href="{{ public_file_url(setting('favicon')) }}">
    @endif

    @include('partials.tracking')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-surface font-sans text-ink antialiased">
    @include('layouts.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('layouts.footer')
</body>
</html>
