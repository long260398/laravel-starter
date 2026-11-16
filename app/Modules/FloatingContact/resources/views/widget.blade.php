@if ($callUrl || $zaloUrl || $messengerUrl)
    <div
        class="fixed bottom-4 {{ $position === 'left' ? 'left-4' : 'right-4' }} z-40 flex flex-col gap-2"
    >
        @if ($callUrl)
            <a
                href="{{ $callUrl }}"
                aria-label="Gọi điện"
                class="motion-safe:animate-pulse flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.5a1 1 0 01.97.757l1.07 4.282a1 1 0 01-.27.95L7.6 10.6a11.04 11.04 0 005.8 5.8l1.61-1.67a1 1 0 01.95-.27l4.282 1.07a1 1 0 01.757.97V19a2 2 0 01-2 2h-1C9.163 21 3 14.837 3 7V5z" />
                </svg>
            </a>
        @endif

        @if ($zaloUrl)
            <a
                href="{{ $zaloUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Chat Zalo"
                class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-500 text-sm font-bold text-white shadow-lg"
            >
                Zalo
            </a>
        @endif

        @if ($messengerUrl)
            <a
                href="{{ $messengerUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Chat Messenger"
                class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.97-4.03 9-9 9a9.01 9.01 0 01-4.52-1.21L3 21l1.29-3.87A8.96 8.96 0 013 12c0-4.97 4.03-9 9-9s9 4.03 9 9z" />
                </svg>
            </a>
        @endif
    </div>
@endif
