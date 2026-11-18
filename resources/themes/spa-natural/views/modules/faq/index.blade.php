@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Câu hỏi thường gặp - '.setting('site_name');
    @endphp

    <section data-theme-view="spa-natural:faq.index" class="mx-auto max-w-[720px] px-4 py-14">
        <h1 class="font-serif text-3xl font-semibold text-ink">Câu hỏi thường gặp</h1>

        @if ($faqs->isEmpty())
            <p class="mt-6 text-sm text-ink-muted">Chưa có câu hỏi nào.</p>
        @else
            <div class="mt-8 divide-y divide-line border-t border-line">
                @foreach ($faqs as $faq)
                    <details class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-serif text-lg font-medium text-ink">
                            {{ $faq->question }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-earth transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </summary>

                        <div class="prose prose-headings:font-serif mt-3 max-w-none text-[15px] leading-[1.7] text-ink-muted">
                            {!! $faq->safe_content !!}
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>

    <script type="application/ld+json">@json($faqSchema)</script>
@endsection
