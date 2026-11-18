@php
    $logoUrl = public_file_url(setting('logo'));
    $siteName = setting('site_name', config('app.name'));
    $zaloUrl = setting('zalo_url');
@endphp

<header class="border-b border-line bg-surface">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 w-auto">
            @else
                <span class="font-serif text-xl font-semibold text-brand">{{ $siteName }}</span>
            @endif
        </a>

        <nav class="hidden flex-1 items-center justify-center gap-6 text-sm font-medium text-ink md:flex">
            <a href="{{ route('home') }}" class="hover:text-brand">Trang chủ</a>
            <a href="{{ route('posts.index') }}" class="hover:text-brand">Tin tức</a>
            @foreach ($navStaticPages as $page)
                <a href="{{ route('static-pages.show', $page->slug) }}" class="hover:text-brand">
                    {{ $page->title }}
                </a>
            @endforeach
            @foreach (module_registry()->navItems() as $item)
                @include($item['view'], $item['data'])
            @endforeach
            <a href="{{ route('contact.create') }}" class="hover:text-brand">Liên hệ</a>
        </nav>

        <div class="hidden shrink-0 md:block">
            @if ($zaloUrl)
                <a
                    href="{{ $zaloUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-dark"
                >
                    Đặt lịch qua Zalo
                </a>
            @endif
        </div>

        <button
            type="button"
            data-mobile-menu-toggle
            aria-expanded="false"
            aria-controls="mobile-menu"
            class="inline-flex items-center justify-center rounded-full p-2 text-ink md:hidden"
        >
            <span class="sr-only">Mở menu</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <nav id="mobile-menu" data-mobile-menu class="hidden border-t border-line px-4 py-4 md:hidden">
        <div class="flex flex-col gap-3 text-sm font-medium text-ink">
            <a href="{{ route('home') }}" class="hover:text-brand">Trang chủ</a>
            <a href="{{ route('posts.index') }}" class="hover:text-brand">Tin tức</a>
            @foreach ($navStaticPages as $page)
                <a href="{{ route('static-pages.show', $page->slug) }}" class="hover:text-brand">
                    {{ $page->title }}
                </a>
            @endforeach
            @foreach (module_registry()->navItems() as $item)
                @include($item['view'], $item['data'])
            @endforeach
            <a href="{{ route('contact.create') }}" class="hover:text-brand">Liên hệ</a>

            @if ($zaloUrl)
                <a
                    href="{{ $zaloUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-2 inline-flex items-center justify-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-medium text-white"
                >
                    Đặt lịch qua Zalo
                </a>
            @endif
        </div>
    </nav>
</header>
