@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Tin tức - '.setting('site_name', config('app.name'));
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-ink">Tin tức</h1>

        @if ($posts->isEmpty())
            <p class="mt-4 text-sm text-ink-muted">Chưa có bài viết nào.</p>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    </section>
@endsection
