@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $service->name.' - '.setting('site_name');
        $seoDescription = $service->short_description ?: str($service->content)->stripTags()->squish()->limit(160);
        $ogImageUrl = public_file_url($service->image);
    @endphp

    <article class="mx-auto max-w-3xl px-4 py-10">
        <p class="text-sm text-ink-muted">{{ $service->category->name }}</p>
        <h1 class="mt-1 text-3xl font-semibold text-ink">{{ $service->name }}</h1>

        <div class="mt-3 flex items-center gap-4 text-sm">
            <span class="font-medium text-brand-700">{{ $service->price_label }}</span>

            @if ($service->duration)
                <span class="text-ink-muted">{{ $service->duration }}</span>
            @endif
        </div>

        @if ($service->image)
            <img src="{{ public_file_url($service->image) }}" alt="{{ $service->name }}" class="mt-6 w-full rounded-lg object-cover">
        @endif

        <div class="prose mt-6 max-w-none text-ink">
            {!! $service->safe_content !!}
        </div>

        <x-module-slot name="service.show.actions" :context="['source' => 'services', 'item' => $service->slug]" />
    </article>
@endsection
