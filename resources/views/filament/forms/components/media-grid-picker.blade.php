<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php($records = $getRecords())
    @php($isMultiple = $field->isMultiple())

    <div @if ($isMultiple) x-data="{ count: {{ count($field->getState() ?? []) }} }" @endif>
        @if ($isMultiple)
            <p class="mb-2 text-sm text-gray-600 dark:text-gray-300">
                Đã chọn <span x-text="count"></span> ảnh
            </p>
        @endif

        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
            @forelse ($records as $media)
                <label class="group relative block cursor-pointer">
                    <input
                        type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                        wire:model="{{ $getStatePath() }}"
                        value="{{ $media->path }}"
                        @if ($isMultiple)
                            x-on:change="count += $event.target.checked ? 1 : -1"
                        @endif
                        class="peer sr-only"
                    />
                    <img
                        src="{{ $media->url }}"
                        alt="{{ $media->alt ?? $media->original_name }}"
                        loading="lazy"
                        class="aspect-square w-full rounded-lg border-2 border-transparent object-cover peer-checked:border-primary-600"
                    />
                </label>
            @empty
                <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">Không có ảnh nào.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
        <button
            type="button"
            x-on:click="$goToPage({ page: {{ $records->currentPage() - 1 }} })"
            @if ($records->onFirstPage()) disabled @endif
            class="disabled:opacity-40"
        >&larr; Trước</button>

        <span>Trang {{ $records->currentPage() }}</span>

        <button
            type="button"
            x-on:click="$goToPage({ page: {{ $records->currentPage() + 1 }} })"
            @if (! $records->hasMorePages()) disabled @endif
            class="disabled:opacity-40"
        >Sau &rarr;</button>
    </div>
</x-dynamic-component>
