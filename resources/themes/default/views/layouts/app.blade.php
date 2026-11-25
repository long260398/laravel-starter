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
        'noindex' => $noindex ?? null,
    ])

    @if (setting('favicon'))
        <link rel="icon" href="{{ public_file_url(setting('favicon')) }}">
    @endif

    @include('partials.tracking')

    @vite([active_theme()->cssEntry(), active_theme()->jsEntry()])
</head>
<body class="flex min-h-screen flex-col bg-surface font-sans text-ink antialiased">
    @include('layouts.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('layouts.footer')

    @foreach (module_registry()->layoutPartials('body_end') as $item)
        @include($item['view'], $item['data'])
    @endforeach
</body>
</html>
