<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Actions\SyncPostTags;
use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /**
     * @var array<int, string>
     */
    protected array $tagNames = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->tagNames = $data['tag_names'] ?? [];
        unset($data['tag_names']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(SyncPostTags::class)->handle($this->record, $this->tagNames);
    }
}
