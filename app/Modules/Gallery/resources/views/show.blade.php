@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $album->title.' - '.setting('site_name');
        $seoDescription = $album->description ? str($album->description)->squish()->limit(160) : null;
        $ogImageUrl = public_file_url($album->cover_image);
    @endphp

    <article class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-3xl font-semibold text-ink">{{ $album->title }}</h1>

        @if ($album->description)
            <p class="mt-3 text-sm text-ink-muted">{{ $album->description }}</p>
        @endif

        @if ($album->images->isEmpty())
            <p class="mt-6 text-sm text-ink-muted">Album chưa có ảnh nào.</p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($album->images as $image)
                    <a
                        href="{{ public_file_url($image->image) }}"
                        data-lightbox="album-{{ $album->slug }}"
                        data-lightbox-caption="{{ $image->caption }}"
                        class="block overflow-hidden rounded-lg"
                    >
                        <img
                            src="{{ public_file_url($image->image) }}"
                            alt="{{ $image->caption ?: $album->title }}"
                            class="h-48 w-full object-cover"
                            loading="lazy"
                        >
                    </a>
                @endforeach
            </div>
        @endif
    </article>
@endsection
