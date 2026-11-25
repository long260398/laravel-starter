@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $post->title.' - '.setting('site_name', config('app.name'));
        $seoDescription = $post->excerpt_for_display;
        $ogImageUrl = public_file_url($post->featured_image);
        $ogType = 'article';
    @endphp

    <script type="application/ld+json">@json($articleJsonLd)</script>
    <script type="application/ld+json">@json($breadcrumbJsonLd)</script>

    <article class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-3xl font-semibold text-ink">{{ $post->title }}</h1>
        <p class="mt-2 text-sm text-ink-muted">
            {{ $post->published_at?->format('d/m/Y') }} · {{ $post->reading_time_minutes }} phút đọc
            @if ($post->category)
                · <a href="{{ route('posts.category-show', $post->category->slug) }}" class="underline">{{ $post->category->name }}</a>
            @endif
        </p>

        @if ($post->featured_image)
            <img src="{{ public_file_url($post->featured_image) }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-lg object-cover">
        @endif

        @if (! empty($tocItems))
            <nav class="mt-6 rounded-lg border border-brand-100 bg-surface-alt p-4" aria-label="Mục lục">
                <p class="text-sm font-semibold text-ink">Mục lục</p>
                <ul class="mt-2 space-y-1 text-sm text-ink-muted">
                    @foreach ($tocItems as $item)
                        <li class="{{ $item['level'] === 3 ? 'ml-4' : '' }}">
                            <a href="#{{ $item['id'] }}" class="hover:text-brand-600">{{ $item['text'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div class="prose mt-6 max-w-none text-ink">
            {!! $tocHtml !!}
        </div>

        @if ($post->tags->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('posts.tag-show', $tag->slug) }}" class="rounded-full border border-brand-100 px-3 py-1 text-xs text-ink-muted hover:text-brand-600">#{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif
    </article>

    @if ($relatedPosts->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-10">
            <h2 class="text-xl font-semibold text-ink">Bài viết liên quan</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($relatedPosts as $relatedPost)
                    <x-post-card :post="$relatedPost" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
