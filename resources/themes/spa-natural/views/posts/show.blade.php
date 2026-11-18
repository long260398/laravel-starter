@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $post->title.' - '.setting('site_name', config('app.name'));
        $seoDescription = str($post->content)->stripTags()->squish()->limit(160);
        $ogImageUrl = public_file_url($post->featured_image);
        $ogType = 'article';
        $image = public_file_url($post->featured_image);
    @endphp

    <article class="mx-auto max-w-[720px] px-4 py-14">
        <h1 class="font-serif text-3xl font-semibold leading-tight text-ink md:text-4xl">{{ $post->title }}</h1>
        <p class="mt-3 text-sm text-ink-muted">{{ $post->published_at?->format('d/m/Y') }}</p>

        @if ($image)
            <img src="{{ $image }}" alt="{{ $post->title }}" class="mt-8 w-full rounded-3xl object-cover">
        @else
            <div class="mt-8 flex h-64 w-full items-center justify-center rounded-3xl border border-line bg-surface-alt text-sm text-ink-muted">
                Ảnh bài viết
            </div>
        @endif

        <div class="prose prose-headings:font-serif mt-8 max-w-none text-[17px] leading-[1.7] text-ink">
            {!! $post->safe_content !!}
        </div>
    </article>
@endsection
