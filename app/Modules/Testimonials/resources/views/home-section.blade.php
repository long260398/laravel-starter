@if ($testimonials->isNotEmpty())
    <section class="mx-auto max-w-6xl px-4 py-10">
        <h2 class="text-2xl font-semibold text-ink">Khách hàng nói gì</h2>

        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($testimonials as $testimonial)
                <blockquote class="rounded-lg bg-surface-muted p-6">
                    <div class="flex items-center gap-3">
                        @if ($testimonial->avatar)
                            <img
                                src="{{ public_file_url($testimonial->avatar) }}"
                                alt="{{ $testimonial->customer_name }}"
                                class="h-10 w-10 rounded-full object-cover"
                            >
                        @endif

                        <div>
                            <p class="font-medium text-ink">{{ $testimonial->customer_name }}</p>

                            @if ($testimonial->customer_title)
                                <p class="text-sm text-ink-muted">{{ $testimonial->customer_title }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($testimonial->rating)
                        <p class="mt-3 text-brand-600" aria-hidden="true">{{ str_repeat('★', $testimonial->rating) }}</p>
                    @endif

                    <p class="mt-3 text-sm text-ink">{{ $testimonial->content }}</p>
                </blockquote>
            @endforeach
        </div>
    </section>
@endif
