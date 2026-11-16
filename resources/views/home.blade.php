@extends('layouts.app')

@section('content')
    <x-banner-slider :banners="$sliderBanners" />

    <section class="mx-auto max-w-6xl px-4 py-10">
        <h2 class="text-2xl font-semibold text-ink">Tin tức mới nhất</h2>

        @if ($latestPosts->isEmpty())
            <p class="mt-4 text-sm text-ink-muted">Chưa có bài viết nào.</p>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latestPosts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>
        @endif
    </section>

    @foreach (module_registry()->homeSections() as $item)
        @include($item['view'], $item['data'])
    @endforeach

    <x-banner-slider :banners="$middleBanners" />
@endsection
