@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $page->meta_title ?: ($page->title.' - '.setting('site_name', config('app.name')));
        $seoDescription = $page->meta_description ?: str($page->content)->stripTags()->squish()->limit(160);
        $ogImageUrl = public_file_url($page->featured_image);
        $image = public_file_url($page->featured_image);
    @endphp

    <article class="mx-auto max-w-[720px] px-4 py-14">
        <h1 class="font-serif text-3xl font-semibold leading-tight text-ink md:text-4xl">{{ $page->title }}</h1>

        @if ($image)
            <img src="{{ $image }}" alt="{{ $page->title }}" class="mt-8 w-full rounded-3xl object-cover">
        @else
            <div class="mt-8 flex h-64 w-full items-center justify-center rounded-3xl border border-line bg-surface-alt text-sm text-ink-muted">
                Ảnh trang
            </div>
        @endif

        <div class="prose prose-headings:font-serif mt-8 max-w-none text-[17px] leading-[1.7] text-ink">
            {!! $page->safe_content !!}
        </div>
    </article>
@endsection
