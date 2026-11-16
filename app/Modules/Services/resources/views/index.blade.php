@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Dịch vụ - '.setting('site_name');
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-ink">Dịch vụ</h1>

        @if ($categories->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                <a
                    href="{{ route('services.index') }}"
                    class="rounded-full px-3 py-1 text-sm {{ $activeCategory === '' ? 'bg-brand-600 text-surface' : 'bg-surface-muted text-ink' }}"
                >
                    Tất cả
                </a>
                @foreach ($categories as $category)
                    <a
                        href="{{ route('services.index', ['category' => $category->slug]) }}"
                        class="rounded-full px-3 py-1 text-sm {{ $activeCategory === $category->slug ? 'bg-brand-600 text-surface' : 'bg-surface-muted text-ink' }}"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($services->isEmpty())
            <p class="mt-6 text-sm text-ink-muted">Chưa có dịch vụ nào.</p>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    @include('services::components.service-card', ['service' => $service])
                @endforeach
            </div>
        @endif
    </section>
@endsection
