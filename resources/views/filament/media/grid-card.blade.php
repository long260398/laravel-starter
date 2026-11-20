@php
    $record = $getRecord();
    $firstUsage = $record->usages->first();
    $isUsed = $firstUsage !== null;
    $label = $firstUsage?->label() ?? 'Chưa dùng';
    $dimensions = ($record->width && $record->height) ? "{$record->width}×{$record->height}" : null;
@endphp

{{--
    w-full + min-w-0 trên chính component này (không phải element con) vì
    wrapper "fi-ta-stack" của Filament tự xếp các con theo hàng ngang (flex)
    — nếu không ép full-width + cho phép co lại ở đây, ảnh to sẽ đẩy cột
    lưới phình ra to theo (CSS flex/grid item mặc định có min-width: auto).
--}}
<div class="w-full min-w-0">
    <div class="relative">
        <img
            src="{{ $record->url }}"
            alt="{{ $record->alt ?? $record->original_name }}"
            loading="lazy"
            class="block aspect-square w-full rounded-lg border border-gray-200 object-cover dark:border-white/10"
        />

        <span class="absolute end-1 top-1 rounded-full px-1.5 py-0.5 text-[0.65rem] font-semibold leading-tight text-white {{ $isUsed ? 'bg-success-600' : 'bg-gray-500' }}">
            {{ $isUsed ? 'Đang dùng' : 'Chưa dùng' }}
        </span>
    </div>

    <div class="mt-1 line-clamp-2 text-xs text-gray-600 dark:text-gray-300" title="{{ $label }}">
        {{ $label }}
    </div>

    @if ($dimensions)
        <div class="text-xs text-gray-400">{{ $dimensions }}</div>
    @endif
</div>
