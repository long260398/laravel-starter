@if ($services->isNotEmpty())
    <section data-reveal data-theme-view="spa-natural:services.home-section" class="mx-auto max-w-6xl px-4 py-14">
        <div class="grid gap-10 md:grid-cols-[1fr_1.4fr]">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-earth">Dịch vụ</p>
                <h2 class="mt-3 font-serif text-3xl font-semibold text-ink">Thực đơn trị liệu</h2>
                <p class="mt-4 max-w-sm text-sm text-ink-muted">
                    Mỗi liệu trình được xây dựng theo nhịp thở chậm — chọn một dịch vụ dưới đây hoặc để
                    chúng tôi tư vấn liệu trình phù hợp với bạn.
                </p>
            </div>

            <div class="divide-y divide-line">
                @foreach ($services as $service)
                    <a
                        href="{{ route('services.show', $service->slug) }}"
                        class="block py-5 first:pt-0 last:pb-0"
                    >
                        <div class="flex items-baseline gap-3">
                            <span class="font-serif text-lg font-medium text-ink">{{ $service->name }}</span>
                            <span class="flex-1 translate-y-[-4px] border-b border-dotted border-line" aria-hidden="true"></span>
                            <span class="shrink-0 text-sm font-medium text-earth">{{ $service->price_label }}</span>
                        </div>

                        @if ($service->short_description || $service->duration)
                            <p class="mt-1.5 text-sm text-ink-muted">
                                {{ $service->short_description }}
                                @if ($service->duration)
                                    <span class="text-ink-muted/80">· {{ $service->duration }}</span>
                                @endif
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
