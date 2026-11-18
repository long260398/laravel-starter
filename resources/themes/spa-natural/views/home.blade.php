@extends('layouts.app')

@section('content')
    @php
        $heroBanner = $sliderBanners->first();
        $heroImage = $heroBanner ? public_file_url($heroBanner->image) : null;
        $middleBanner = $middleBanners->first();
    @endphp

    {{-- Hero: chữ trái, ảnh vòm phải. Banner không có trường mô tả dài nên
    title -> h1, subtitle -> đoạn mô tả; eyebrow lấy từ slogan (không query
    thêm dữ liệu nhóm dịch vụ trong view). --}}
    {{-- Hero nằm trong màn hình đầu tiên — không gắn data-reveal, luôn hiện ngay. --}}
    <section class="mx-auto grid max-w-6xl gap-10 px-4 py-14 md:grid-cols-2 md:items-center md:py-20">
        <div>
            @if (setting('slogan'))
                <p class="text-sm font-medium uppercase tracking-wide text-earth">{{ setting('slogan') }}</p>
            @endif

            <h1 class="mt-3 font-serif text-4xl font-semibold leading-tight text-ink md:text-5xl">
                {{ $heroBanner?->title ?? setting('site_name', config('app.name')) }}
            </h1>

            @if ($heroBanner?->subtitle)
                <p class="mt-5 max-w-md text-base text-ink-muted">{{ $heroBanner->subtitle }}</p>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3">
                @if (setting('zalo_url'))
                    <a
                        href="{{ setting('zalo_url') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-medium text-white transition hover:bg-brand-dark"
                    >
                        Đặt lịch qua Zalo
                    </a>
                @endif

                @if (setting('hotline'))
                    <a
                        href="tel:{{ preg_replace('/\D/', '', setting('hotline')) }}"
                        class="inline-flex items-center gap-2 rounded-full border border-line px-6 py-3 text-sm font-medium text-ink transition hover:border-ink"
                    >
                        Gọi {{ setting('hotline') }}
                    </a>
                @endif
            </div>

            @if (setting('working_hours') || setting('address'))
                <dl class="mt-8 flex flex-col gap-2 border-t border-line pt-5 text-sm text-ink-muted sm:flex-row sm:gap-8">
                    @if (setting('working_hours'))
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-earth" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                            </svg>
                            <span>{{ setting('working_hours') }}</span>
                        </div>
                    @endif

                    @if (setting('address'))
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-earth" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 6-7.5 11-7.5 11s-7.5-5-7.5-11a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            <span>{{ setting('address') }}</span>
                        </div>
                    @endif
                </dl>
            @endif
        </div>

        <div>
            @if ($heroImage)
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $heroBanner->title }}"
                    class="arch-top h-[420px] w-full object-cover md:h-[480px]"
                >
            @else
                <div class="arch-top flex h-[420px] w-full flex-col items-center justify-center gap-2 border border-line bg-brand/10 text-center md:h-[480px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-brand/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75 8.25 9.9a1.5 1.5 0 0 1 2.12 0l2.26 2.26M6 20.25h12a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 9v9A2.25 2.25 0 0 0 6 20.25Z" />
                        <circle cx="15" cy="9" r="1.25" />
                    </svg>
                    <p class="text-sm text-ink-muted">Ảnh đại diện spa</p>
                </div>
            @endif
        </div>
    </section>

    @if ($middleBanner)
        @php
            $middleHasLink = filled($middleBanner->link_url) && \App\Models\Banner::isSafeLinkUrl($middleBanner->link_url);
        @endphp

        <section data-reveal class="bg-earth py-10 text-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 px-4 text-center sm:flex-row sm:justify-between sm:text-left">
                <div>
                    <p class="font-serif text-2xl font-semibold">{{ $middleBanner->title }}</p>

                    @if ($middleBanner->subtitle)
                        <p class="mt-1 text-sm text-white/85">{{ $middleBanner->subtitle }}</p>
                    @endif
                </div>

                @if ($middleBanner->button_text)
                    @if ($middleHasLink)
                        <a
                            href="{{ $middleBanner->link_url }}"
                            @if ($middleBanner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                            class="inline-flex shrink-0 items-center rounded-full bg-white px-6 py-2.5 text-sm font-medium text-earth"
                        >
                            {{ $middleBanner->button_text }}
                        </a>
                    @else
                        <span class="inline-flex shrink-0 items-center rounded-full bg-white px-6 py-2.5 text-sm font-medium text-earth">
                            {{ $middleBanner->button_text }}
                        </span>
                    @endif
                @endif
            </div>
        </section>
    @endif

    @foreach (module_registry()->homeSections() as $item)
        @include($item['view'], $item['data'])
    @endforeach

    <section data-reveal class="mx-auto max-w-6xl px-4 py-14">
        <h2 class="font-serif text-2xl font-semibold text-ink">Tin tức mới nhất</h2>

        @if ($latestPosts->isEmpty())
            <p class="mt-4 text-sm text-ink-muted">Chưa có bài viết nào.</p>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latestPosts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>
        @endif
    </section>
@endsection
