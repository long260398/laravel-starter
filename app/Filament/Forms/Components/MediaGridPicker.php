<?php

namespace App\Filament\Forms\Components;

use App\Models\Media;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lưới ảnh chọn 1 (radio, bind `wire:model` thẳng vào state của field) dùng
 * trong modal hint action của {@see MediaPicker}. Tìm kiếm + phân trang đọc
 * state 2 field anh em `search`/`page` khai báo cùng cấp trong schema của
 * action — không dùng Livewire component lồng, chỉ dựa vào `$get()`/`$set()`
 * sẵn có của Filament cho field tùy biến (xem docs/STANDARDS.md).
 */
class MediaGridPicker extends Field
{
    protected string $view = 'filament.forms.components.media-grid-picker';

    protected string|Closure $disk = 'public';

    /**
     * @var array<int, string>|Closure
     */
    protected array|Closure $acceptedMimeTypes = [];

    protected int $perPage = 12;

    public function disk(string|Closure $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * @param  array<int, string>|Closure  $mimeTypes
     */
    public function acceptedMimeTypes(array|Closure $mimeTypes): static
    {
        $this->acceptedMimeTypes = $mimeTypes;

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function getDiskName(): string
    {
        return $this->evaluate($this->disk);
    }

    /**
     * @return array<int, string>
     */
    public function getAcceptedMimeTypes(): array
    {
        return $this->evaluate($this->acceptedMimeTypes) ?? [];
    }

    public function getRecords(): LengthAwarePaginator
    {
        return $this->evaluate(function (Get $get): LengthAwarePaginator {
            $search = trim((string) ($get('search') ?? ''));
            $page = max(1, (int) ($get('page') ?? 1));
            $mimeTypes = $this->getAcceptedMimeTypes();

            return Media::query()
                ->where('disk', $this->getDiskName())
                ->when($mimeTypes !== [], fn ($query) => $query->whereIn('mime_type', $mimeTypes))
                ->when($search !== '', fn ($query) => $query->where(
                    fn ($query) => $query->where('original_name', 'like', "%{$search}%")
                        ->orWhere('alt', 'like', "%{$search}%"),
                ))
                ->orderByDesc('created_at')
                ->paginate($this->perPage, ['*'], 'page', $page);
        });
    }

    #[Exposed]
    public function goToPage(int $page): void
    {
        $this->evaluate(function (Set $set) use ($page): void {
            $set('page', max(1, $page));
        });
    }
}
