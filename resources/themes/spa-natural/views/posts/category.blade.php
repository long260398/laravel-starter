@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $category->name.' - '.setting('site_name', config('app.name'));
        $seoDescription = $category->description;
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-14">
        <h1 class="font-serif text-3xl font-semibold text-ink">{{ $category->name }}</h1>

        @if ($category->description)
            <p class="mt-3 text-sm text-ink-muted">{{ $category->description }}</p>
        @endif

        @if ($posts->isEmpty())
            <p class="mt-4 text-sm text-ink-muted">Chưa có bài viết nào trong chuyên mục này.</p>
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
