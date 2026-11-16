@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Thư viện ảnh - '.setting('site_name');
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-ink">Thư viện ảnh</h1>

        @if ($albums->isEmpty())
            <p class="mt-6 text-sm text-ink-muted">Chưa có album nào.</p>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($albums as $album)
                    @include('gallery::components.album-card', ['album' => $album])
                @endforeach
            </div>
        @endif
    </section>
@endsection
