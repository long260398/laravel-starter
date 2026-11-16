@php
    $socialLinks = [
        'Facebook' => setting('facebook_url'),
        'Zalo' => setting('zalo_url'),
        'TikTok' => setting('tiktok_url'),
        'YouTube' => setting('youtube_url'),
        'Messenger' => setting('messenger_url'),
    ];
@endphp

<footer class="mt-auto border-t border-brand-100 bg-surface-muted">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-2">
        <div>
            <p class="text-lg font-semibold text-brand-700">{{ setting('site_name', config('app.name')) }}</p>

            @if (setting('slogan'))
                <p class="mt-1 text-sm text-ink-muted">{{ setting('slogan') }}</p>
            @endif

            <dl class="mt-4 space-y-1 text-sm text-ink">
                @if (setting('address'))
                    <div class="flex gap-2">
                        <dt class="font-medium">Địa chỉ:</dt>
                        <dd>{{ setting('address') }}</dd>
                    </div>
                @endif

                @if (setting('hotline'))
                    <div class="flex gap-2">
                        <dt class="font-medium">Hotline:</dt>
                        <dd><a href="tel:{{ setting('hotline') }}" class="hover:text-brand-600">{{ setting('hotline') }}</a></dd>
                    </div>
                @endif

                @if (setting('contact_email'))
                    <div class="flex gap-2">
                        <dt class="font-medium">Email:</dt>
                        <dd><a href="mailto:{{ setting('contact_email') }}" class="hover:text-brand-600">{{ setting('contact_email') }}</a></dd>
                    </div>
                @endif

                @if (setting('working_hours'))
                    <div class="flex gap-2">
                        <dt class="font-medium">Giờ làm việc:</dt>
                        <dd>{{ setting('working_hours') }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="md:text-right">
            <p class="text-sm font-medium text-ink">Kết nối với chúng tôi</p>
            <div class="mt-3 flex flex-wrap gap-4 md:justify-end">
                @foreach ($socialLinks as $label => $url)
                    @if ($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-sm text-ink hover:text-brand-600">
                            {{ $label }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="border-t border-brand-100 py-4 text-center text-xs text-ink-muted">
        &copy; {{ now()->year }} {{ setting('site_name', config('app.name')) }}. Đã đăng ký bản quyền.
    </div>
</footer>
