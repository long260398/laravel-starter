<article class="flex flex-col overflow-hidden rounded-lg border border-brand-100 bg-surface">
    <a href="{{ route('services.show', $service->slug) }}" class="block">
        @if ($service->image)
            <img src="{{ public_file_url($service->image) }}" alt="{{ $service->name }}" class="h-48 w-full object-cover" loading="lazy">
        @endif

        <div class="p-4">
            <h3 class="text-lg font-semibold text-ink">{{ $service->name }}</h3>

            @if ($service->short_description)
                <p class="mt-2 text-sm text-ink-muted">{{ $service->short_description }}</p>
            @endif

            <div class="mt-3 flex items-center justify-between text-sm">
                <span class="font-medium text-brand-700">{{ $service->price_label }}</span>

                @if ($service->duration)
                    <span class="text-ink-muted">{{ $service->duration }}</span>
                @endif
            </div>
        </div>
    </a>
</article>
