@extends('layouts.app')

@section('content')
    @php
        $seoTitle = 'Câu hỏi thường gặp - '.setting('site_name');
    @endphp

    <section class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-2xl font-semibold text-ink">Câu hỏi thường gặp</h1>

        @if ($faqs->isEmpty())
            <p class="mt-6 text-sm text-ink-muted">Chưa có câu hỏi nào.</p>
        @else
            <div class="mt-6 space-y-3">
                @foreach ($faqs as $faq)
                    <details class="rounded-lg border border-surface-muted p-4">
                        <summary class="cursor-pointer font-medium text-ink">{{ $faq->question }}</summary>

                        <div class="prose mt-3 max-w-none text-ink">
                            {!! $faq->safe_content !!}
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>

    <script type="application/ld+json">@json($faqSchema)</script>
@endsection
