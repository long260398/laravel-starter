<?php

namespace App\Modules\Services\Filament\Resources\ServiceCategories;

use App\Modules\Services\Filament\Resources\ServiceCategories\Pages\CreateServiceCategory;
use App\Modules\Services\Filament\Resources\ServiceCategories\Pages\EditServiceCategory;
use App\Modules\Services\Filament\Resources\ServiceCategories\Pages\ListServiceCategories;
use App\Modules\Services\Filament\Resources\ServiceCategories\Schemas\ServiceCategoryForm;
use App\Modules\Services\Filament\Resources\ServiceCategories\Tables\ServiceCategoriesTable;
use App\Modules\Services\Models\ServiceCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'Danh mục dịch vụ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Danh mục dịch vụ';
    }

    public static function getNavigationLabel(): string
    {
        return 'Danh mục dịch vụ';
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceCategories::route('/'),
            'create' => CreateServiceCategory::route('/create'),
            'edit' => EditServiceCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
