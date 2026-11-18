@props(['banners'])

@php
    $banners = $banners instanceof \Illuminate\Support\Collection ? $banners : collect($banners);
@endphp

@if ($banners->isNotEmpty())
    <div data-banner-slider data-banner-interval="6000" class="group relative overflow-hidden rounded-3xl border border-line">
        <div data-banner-track class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none]">
            @foreach ($banners as $banner)
                @php
                    $image = public_file_url($banner->image);
                    $hasLink = filled($banner->link_url) && \App\Models\Banner::isSafeLinkUrl($banner->link_url);
                @endphp

                <div class="relative w-full shrink-0 snap-start">
                    @if ($hasLink)
                        <a
                            href="{{ $banner->link_url }}"
                            @if ($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                            class="block"
                        >
                    @endif

                    @if ($image)
                        <img
                            src="{{ $image }}"
                            alt="{{ $banner->title }}"
                            class="h-full w-full object-cover"
                            @if (! $loop->first) loading="lazy" @endif
                        >
                    @else
                        <div class="flex h-64 w-full items-center justify-center bg-brand/10 text-sm text-ink-muted">
                            Ảnh banner: {{ $banner->title }}
                        </div>
                    @endif

                    @if ($banner->subtitle || $banner->button_text)
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink/80 to-transparent p-6 text-white">
                            @if ($banner->subtitle)
                                <p class="font-serif text-lg font-semibold">{{ $banner->subtitle }}</p>
                            @endif

                            @if ($banner->button_text)
                                <span class="mt-2 inline-block rounded-full bg-brand px-4 py-2 text-sm font-medium">
                                    {{ $banner->button_text }}
                                </span>
                            @endif
                        </div>
                    @endif

                    @if ($hasLink)
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($banners->count() > 1)
            <button type="button" data-banner-prev aria-label="Banner trước" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-surface/80 p-2 opacity-0 transition group-hover:opacity-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <button type="button" data-banner-next aria-label="Banner kế tiếp" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-surface/80 p-2 opacity-0 transition group-hover:opacity-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>

            <div class="absolute inset-x-0 bottom-2 flex justify-center gap-2">
                @foreach ($banners as $index => $banner)
                    <button
                        type="button"
                        data-banner-dot
                        aria-label="Đi tới banner {{ $index + 1 }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        class="h-2 w-2 rounded-full bg-surface/70 aria-[current=true]:bg-surface"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
