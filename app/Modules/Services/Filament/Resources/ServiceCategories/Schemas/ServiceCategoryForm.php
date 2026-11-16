<?php

namespace App\Modules\Services\Filament\Resources\ServiceCategories\Schemas;

use App\Enums\PublishStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên danh mục')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set, callable $get): void {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Đường dẫn (slug)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(PublishStatus::class)
                    ->required()
                    ->native(false),
            ]);
    }
}
