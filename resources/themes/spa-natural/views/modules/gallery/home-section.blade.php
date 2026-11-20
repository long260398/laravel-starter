@php
    $bigAlbum = $albums->first();
    $smallAlbums = $albums->slice(1, 2);
@endphp

@if ($bigAlbum)
    <section data-reveal data-theme-view="spa-natural:gallery.home-section" class="mx-auto max-w-6xl px-4 py-14">
        <p class="text-sm font-medium uppercase tracking-wide text-earth">Không gian</p>
        <h2 class="mt-3 font-serif text-3xl font-semibold text-ink">Một góc yên tĩnh</h2>

        <div class="mt-8 grid gap-4 md:grid-cols-[1.4fr_1fr]">
            <a href="{{ route('gallery.show', $bigAlbum->slug) }}" class="block">
                @if ($bigAlbum->cover_url)
                    <img
                        src="{{ $bigAlbum->cover_url }}"
                        alt="{{ $bigAlbum->title }}"
                        class="h-full max-h-[32rem] w-full rounded-3xl object-cover"
                    >
                @else
                    <div class="flex h-80 w-full items-center justify-center rounded-3xl border border-line bg-surface-alt text-sm text-ink-muted md:h-full md:max-h-[32rem]">
                        Ảnh không gian: {{ $bigAlbum->title }}
                    </div>
                @endif
            </a>

            <div class="flex flex-col gap-4">
                @foreach ($smallAlbums as $album)
                    <a href="{{ route('gallery.show', $album->slug) }}" class="block flex-1">
                        @if ($album->cover_url)
                            <img
                                src="{{ $album->cover_url }}"
                                alt="{{ $album->title }}"
                                class="h-full min-h-[9rem] w-full rounded-3xl object-cover"
                            >
                        @else
                            <div class="flex h-full min-h-[9rem] w-full items-center justify-center rounded-3xl border border-line bg-surface-alt text-sm text-ink-muted">
                                Ảnh không gian: {{ $album->title }}
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
