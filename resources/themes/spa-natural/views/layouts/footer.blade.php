@php
    $hasContactInfo = filled(setting('address')) || filled(setting('hotline')) || filled(setting('working_hours'));

    $quickLinks = array_filter([
        'hotline' => setting('hotline') ? ['label' => setting('hotline'), 'href' => 'tel:'.preg_replace('/\D/', '', setting('hotline'))] : null,
        'zalo' => setting('zalo_url') ? ['label' => 'Zalo', 'href' => setting('zalo_url')] : null,
        'facebook' => setting('facebook_url') ? ['label' => 'Facebook', 'href' => setting('facebook_url')] : null,
    ]);
@endphp

<footer class="mt-auto bg-footer text-footer-ink">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 md:grid-cols-2">
        <div>
            <p class="font-serif text-xl font-semibold">{{ setting('site_name', config('app.name')) }}</p>

            @if (setting('slogan'))
                <p class="mt-2 text-sm italic text-footer-ink/80">{{ setting('slogan') }}</p>
            @endif

            @if ($hasContactInfo)
                <dl class="mt-5 space-y-2 text-sm text-footer-ink/90">
                    @if (setting('address'))
                        <div class="flex gap-2">
                            <dt class="font-medium">Địa chỉ:</dt>
                            <dd>{{ setting('address') }}</dd>
                        </div>
                    @endif

                    @if (setting('working_hours'))
                        <div class="flex gap-2">
                            <dt class="font-medium">Giờ mở cửa:</dt>
                            <dd>{{ setting('working_hours') }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        </div>

        @if ($quickLinks !== [])
            <div class="md:text-right">
                <p class="text-sm font-medium">Kết nối với chúng tôi</p>
                <div class="mt-4 flex flex-wrap gap-3 md:justify-end">
                    @if (isset($quickLinks['hotline']))
                        <a
                            href="{{ $quickLinks['hotline']['href'] }}"
                            aria-label="Gọi hotline {{ $quickLinks['hotline']['label'] }}"
                            class="inline-flex items-center gap-2 rounded-full border border-footer-ink/30 px-4 py-2 text-sm hover:border-footer-ink"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h.75a2.25 2.25 0 0 0 2.25-2.25v-1.372a1.5 1.5 0 0 0-1.009-1.417l-3.236-1.079a1.5 1.5 0 0 0-1.591.395l-.598.699a11.05 11.05 0 0 1-5.593-5.593l.699-.598a1.5 1.5 0 0 0 .395-1.591L8.24 4.26A1.5 1.5 0 0 0 6.823 3.25H5.25A2.25 2.25 0 0 0 3 5.5v1.25" />
                            </svg>
                            {{ $quickLinks['hotline']['label'] }}
                        </a>
                    @endif

                    @if (isset($quickLinks['zalo']))
                        <a
                            href="{{ $quickLinks['zalo']['href'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Nhắn Zalo"
                            class="inline-flex items-center gap-2 rounded-full border border-footer-ink/30 px-4 py-2 text-sm hover:border-footer-ink"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 10.5h7.5m-7.5 3h4.5M21 12c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 0 1-3.08-.497c-.45-.153-.946-.084-1.337.183l-1.95 1.316a.75.75 0 0 1-1.157-.665l.09-2.06a.75.75 0 0 0-.274-.59C2.47 16.638 1 14.47 1 12c0-4.556 4.477-8.25 9.5-8.25S21 7.444 21 12Z" />
                            </svg>
                            Zalo
                        </a>
                    @endif

                    @if (isset($quickLinks['facebook']))
                        <a
                            href="{{ $quickLinks['facebook']['href'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Theo dõi Facebook"
                            class="inline-flex items-center gap-2 rounded-full border border-footer-ink/30 px-4 py-2 text-sm hover:border-footer-ink"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 8.25h-1.5a1.5 1.5 0 0 0-1.5 1.5v1.5H9v2.25h2.25V21h2.25v-7.5h2.1l.4-2.25h-2.5V9.9c0-.414.336-.65.75-.65h1.9V8.25Z" />
                            </svg>
                            Facebook
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="border-t border-footer-ink/20 py-4 text-center text-xs text-footer-ink/70">
        &copy; {{ now()->year }} {{ setting('site_name', config('app.name')) }}. Đã đăng ký bản quyền.
    </div>
</footer>
