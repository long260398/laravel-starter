@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Tìm kiếm bài viết - '.setting('site_name', config('app.name'));
    @endphp

    <section class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-ink">Tìm kiếm bài viết</h1>

        <form action="{{ route('posts.search') }}" method="GET" class="mt-6 flex gap-2">
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                placeholder="Nhập từ khoá..."
                class="w-full max-w-md rounded-lg border border-brand-100 px-4 py-2 text-sm text-ink"
            >
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Tìm</button>
        </form>

        @if ($posts === null)
            <p class="mt-4 text-sm text-ink-muted">Nhập ít nhất 2 ký tự để tìm kiếm.</p>
        @elseif ($posts->isEmpty())
            <p class="mt-4 text-sm text-ink-muted">Không tìm thấy bài viết nào cho "{{ $query }}".</p>
        @else
            <p class="mt-4 text-sm text-ink-muted">Kết quả cho "{{ $query }}":</p>

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
