@if ($services->isNotEmpty())
    <section class="mx-auto max-w-6xl px-4 py-10">
        <h2 class="text-2xl font-semibold text-ink">Dịch vụ nổi bật</h2>

        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                @include('services::components.service-card', ['service' => $service])
            @endforeach
        </div>
    </section>
@endif
