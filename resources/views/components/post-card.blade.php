@props(['post'])

@php
    $image = public_file_url($post->featured_image);
    $excerpt = str($post->content)->stripTags()->squish()->limit(140);
@endphp

<article class="flex flex-col overflow-hidden rounded-lg border border-brand-100 bg-surface">
    <a href="{{ route('posts.show', $post->slug) }}" class="block">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $post->title }}" class="h-48 w-full object-cover" loading="lazy">
        @endif

        <div class="p-4">
            <h3 class="text-lg font-semibold text-ink group-hover:text-brand-600">{{ $post->title }}</h3>
            <p class="mt-2 text-sm text-ink-muted">{{ $excerpt }}</p>
            <p class="mt-3 text-xs text-ink-muted">{{ $post->published_at?->format('d/m/Y') }}</p>
        </div>
    </a>
</article>
