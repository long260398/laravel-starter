@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $post->title.' - '.setting('site_name', config('app.name'));
        $seoDescription = str($post->content)->stripTags()->squish()->limit(160);
        $ogImageUrl = public_file_url($post->featured_image);
        $ogType = 'article';
    @endphp

    <article class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-3xl font-semibold text-ink">{{ $post->title }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ $post->published_at?->format('d/m/Y') }}</p>

        @if ($post->featured_image)
            <img src="{{ public_file_url($post->featured_image) }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-lg object-cover">
        @endif

        <div class="prose mt-6 max-w-none text-ink">
            {!! $post->safe_content !!}
        </div>
    </article>
@endsection
