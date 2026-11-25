@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Thẻ: '.$tag->name.' - '.setting('site_name', config('app.name'));
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-14">
        <h1 class="font-serif text-3xl font-semibold text-ink">Thẻ: {{ $tag->name }}</h1>

        @if ($posts->isEmpty())
            <p class="mt-4 text-sm text-ink-muted">Chưa có bài viết nào cho thẻ này.</p>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @endif
    </section>
@endsection
