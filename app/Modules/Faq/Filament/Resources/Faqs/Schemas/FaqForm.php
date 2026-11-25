<?php

namespace App\Modules\Faq\Filament\Resources\Faqs\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question')
                    ->label('Câu hỏi')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                RichEditor::make('answer')
                    ->label('Câu trả lời')
                    ->required()
                    ->fileAttachmentsDisk(config('media.disk'))
                    ->fileAttachmentsDirectory('content')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
            ]);
    }
}
