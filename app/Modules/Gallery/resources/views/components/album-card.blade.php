<article class="flex flex-col overflow-hidden rounded-lg border border-brand-100 bg-surface">
    <a href="{{ route('gallery.show', $album->slug) }}" class="block">
        @if ($album->cover_url)
            <img src="{{ $album->cover_url }}" alt="{{ $album->title }}" class="h-48 w-full object-cover" loading="lazy">
        @endif

        <div class="p-4">
            <h3 class="text-lg font-semibold text-ink">{{ $album->title }}</h3>

            @if ($album->description)
                <p class="mt-2 text-sm text-ink-muted">{{ $album->description }}</p>
            @endif
        </div>
    </a>
</article>
