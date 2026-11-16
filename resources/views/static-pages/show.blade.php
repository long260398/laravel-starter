@extends('layouts.app')

@section('content')
    @php
        $seoTitle = $page->meta_title ?: ($page->title.' - '.setting('site_name'));
        $seoDescription = $page->meta_description ?: str($page->content)->stripTags()->squish()->limit(160);
        $ogImageUrl = public_file_url($page->featured_image);
    @endphp

    <article class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-3xl font-semibold text-ink">{{ $page->title }}</h1>

        @if ($page->featured_image)
            <img src="{{ public_file_url($page->featured_image) }}" alt="{{ $page->title }}" class="mt-6 w-full rounded-lg object-cover">
        @endif

        <div class="prose mt-6 max-w-none text-ink">
            {!! $page->safe_content !!}
        </div>
    </article>
@endsection
