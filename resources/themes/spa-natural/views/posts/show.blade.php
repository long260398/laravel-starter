@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $post->title.' - '.setting('site_name', config('app.name'));
        $seoDescription = $post->excerpt_for_display;
        $ogImageUrl = public_file_url($post->featured_image);
        $ogType = 'article';
        $image = public_file_url($post->featured_image);
    @endphp

    <script type="application/ld+json">@json($articleJsonLd)</script>
    <script type="application/ld+json">@json($breadcrumbJsonLd)</script>

    <article class="mx-auto max-w-[720px] px-4 py-14">
        <h1 class="font-serif text-3xl font-semibold leading-tight text-ink md:text-4xl">{{ $post->title }}</h1>
        <p class="mt-3 text-sm text-ink-muted">
            {{ $post->published_at?->format('d/m/Y') }} · {{ $post->reading_time_minutes }} phút đọc
            @if ($post->category)
                · <a href="{{ route('posts.category-show', $post->category->slug) }}" class="underline">{{ $post->category->name }}</a>
            @endif
        </p>

        @if ($image)
            <img src="{{ $image }}" alt="{{ $post->title }}" class="mt-8 w-full rounded-3xl object-cover">
        @else
            <div class="mt-8 flex h-64 w-full items-center justify-center rounded-3xl border border-line bg-surface-alt text-sm text-ink-muted">
                Ảnh bài viết
            </div>
        @endif

        @if (! empty($tocItems))
            <nav class="mt-8 rounded-3xl border border-line bg-surface-alt p-5" aria-label="Mục lục">
                <p class="font-serif text-sm font-semibold text-ink">Mục lục</p>
                <ul class="mt-3 space-y-1 text-sm text-ink-muted">
                    @foreach ($tocItems as $item)
                        <li class="{{ $item['level'] === 3 ? 'ml-4' : '' }}">
                            <a href="#{{ $item['id'] }}" class="hover:text-brand">{{ $item['text'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div class="prose prose-headings:font-serif mt-8 max-w-none text-[17px] leading-[1.7] text-ink">
            {!! $tocHtml !!}
        </div>

        @if ($post->tags->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('posts.tag-show', $tag->slug) }}" class="rounded-full border border-line px-3 py-1 text-xs text-ink-muted hover:text-brand">#{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif
    </article>

    @if ($relatedPosts->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-14">
            <h2 class="font-serif text-2xl font-semibold text-ink">Bài viết liên quan</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($relatedPosts as $relatedPost)
                    <x-post-card :post="$relatedPost" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
