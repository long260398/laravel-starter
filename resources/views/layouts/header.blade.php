@php
    $logoUrl = public_file_url(setting('logo'));
    $siteName = setting('site_name', config('app.name'));
    $navStaticPages = \App\Models\StaticPage::query()->published()->get();
@endphp

<header class="border-b border-brand-100 bg-surface">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 w-auto">
            @else
                <span class="text-xl font-semibold text-brand-700">{{ $siteName }}</span>
            @endif
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium text-ink md:flex">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Trang chủ</a>
            <a href="{{ route('posts.index') }}" class="hover:text-brand-600">Tin tức</a>
            @foreach ($navStaticPages as $page)
                <a href="{{ route('static-pages.show', $page->slug) }}" class="hover:text-brand-600">
                    {{ $page->title }}
                </a>
            @endforeach
            @foreach (module_registry()->navItems() as $item)
                @include($item['view'], $item['data'])
            @endforeach
            <a href="{{ route('contact.create') }}" class="hover:text-brand-600">Liên hệ</a>
        </nav>

        <button
            type="button"
            data-mobile-menu-toggle
            aria-expanded="false"
            aria-controls="mobile-menu"
            class="inline-flex items-center justify-center rounded-md p-2 text-ink md:hidden"
        >
            <span class="sr-only">Mở menu</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <nav id="mobile-menu" data-mobile-menu class="hidden border-t border-brand-100 px-4 py-3 md:hidden">
        <div class="flex flex-col gap-3 text-sm font-medium text-ink">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Trang chủ</a>
            <a href="{{ route('posts.index') }}" class="hover:text-brand-600">Tin tức</a>
            @foreach ($navStaticPages as $page)
                <a href="{{ route('static-pages.show', $page->slug) }}" class="hover:text-brand-600">
                    {{ $page->title }}
                </a>
            @endforeach
            @foreach (module_registry()->navItems() as $item)
                @include($item['view'], $item['data'])
            @endforeach
            <a href="{{ route('contact.create') }}" class="hover:text-brand-600">Liên hệ</a>
        </div>
    </nav>
</header>
