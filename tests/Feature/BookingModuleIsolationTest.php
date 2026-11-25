<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Module Booking phải tách rời hoàn toàn: xoá app/Modules/Booking + entry
 * `booking` trong config/modules.php phải sạch, không còn chỗ nào khác tham
 * chiếu tới nó (xem docs/STANDARDS.md mục "Module tùy chọn").
 *
 * Chỉ quét code "chạy thật" (app/, routes/, config/, resources/, database/) —
 * không quét `tests/`: test của Booking (giống ServiceModuleDisabledTest,
 * GalleryModuleDisabledTest... của các module khác) vẫn sống ở `tests/`
 * theo đúng convention hiện có của dự án và tất nhiên phải nhắc tới
 * "Booking" rất nhiều — việc đó không phải rò rỉ phụ thuộc.
 */
class BookingModuleIsolationTest extends TestCase
{
    public function test_no_file_outside_the_booking_module_mentions_booking(): void
    {
        $offenders = [];

        foreach ($this->scannedFiles() as $absolutePath) {
            if (str_contains(mb_strtolower(File::get($absolutePath)), 'booking')) {
                $offenders[] = $this->toRelativePath($absolutePath);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Các file dưới đây nằm ngoài app/Modules/Booking nhưng có chữ \"booking\":\n".implode("\n", $offenders)
        );
    }

    /**
     * @return array<int, string>
     */
    private function scannedFiles(): array
    {
        $roots = ['app', 'routes', 'config', 'resources', 'database'];

        $sourceExtensions = ['php', 'js', 'css', 'json'];

        return collect($roots)
            ->filter(fn (string $root): bool => is_dir(base_path($root)))
            ->flatMap(fn (string $root) => File::allFiles(base_path($root)))
            ->filter(fn ($file) => in_array($file->getExtension(), $sourceExtensions, true))
            ->map(fn ($file): string => $file->getPathname())
            ->reject(fn (string $path): bool => $this->isExcluded($path))
            ->values()
            ->all();
    }

    private function isExcluded(string $path): bool
    {
        $relative = $this->toRelativePath($path);

        return $relative === 'config/modules.php'
            || str_starts_with($relative, 'app/Modules/Booking/')
            || str_contains($relative, '/views/modules/booking/');
    }

    private function toRelativePath(string $absolutePath): string
    {
        return str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $absolutePath));
    }
}
