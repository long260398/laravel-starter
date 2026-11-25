<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Actions\SyncPostTags;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    /**
     * @var array<int, string>
     */
    protected array $tagNames = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tag_names'] = $this->record->tags->pluck('name')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->tagNames = $data['tag_names'] ?? [];
        unset($data['tag_names']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(SyncPostTags::class)->handle($this->record, $this->tagNames);
    }
}
