@props(['post'])

@php
    $image = public_file_url($post->featured_image);
    $excerpt = str($post->excerpt_for_display)->limit(140);
@endphp

<article class="flex flex-col overflow-hidden rounded-3xl border border-line bg-card">
    <a href="{{ route('posts.show', $post->slug) }}" class="block">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $post->title }}" class="h-48 w-full object-cover" loading="lazy">
        @else
            <div class="flex h-48 w-full items-center justify-center bg-surface-alt text-sm text-ink-muted">
                Ảnh bài viết
            </div>
        @endif

        <div class="p-5">
            <h3 class="font-serif text-lg font-semibold text-ink group-hover:text-brand">{{ $post->title }}</h3>
            <p class="mt-2 text-sm text-ink-muted">{{ $excerpt }}</p>
            <p class="mt-3 text-xs text-ink-muted">{{ $post->published_at?->format('d/m/Y') }}</p>
        </div>
    </a>
</article>
