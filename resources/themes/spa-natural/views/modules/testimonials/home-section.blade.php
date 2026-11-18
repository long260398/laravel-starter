@if ($testimonials->isNotEmpty())
    <section data-reveal data-theme-view="spa-natural:testimonials.home-section" class="bg-surface-alt py-14">
        <div class="mx-auto max-w-6xl px-4">
            <p class="text-sm font-medium uppercase tracking-wide text-earth">Cảm nhận</p>
            <h2 class="mt-3 font-serif text-3xl font-semibold text-ink">Khách hàng nói gì</h2>

            <div class="mt-10 grid gap-10 md:grid-cols-3">
                @foreach ($testimonials as $testimonial)
                    <figure>
                        <blockquote class="font-serif text-lg italic leading-relaxed text-ink">
                            “{{ $testimonial->content }}”
                        </blockquote>

                        <figcaption class="mt-4 flex items-center gap-3">
                            @if ($testimonial->avatar)
                                <img
                                    src="{{ public_file_url($testimonial->avatar) }}"
                                    alt="{{ $testimonial->customer_name }}"
                                    class="h-10 w-10 rounded-full object-cover"
                                >
                            @endif

                            <div>
                                <p class="text-sm font-medium text-ink">{{ $testimonial->customer_name }}</p>

                                @if ($testimonial->customer_title)
                                    <p class="text-xs text-ink-muted">{{ $testimonial->customer_title }}</p>
                                @endif
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
