<?php

namespace App\Modules\Services\Filament\Resources\Services;

use App\Enums\NavigationGroup;
use App\Modules\Services\Filament\Resources\Services\Pages\CreateService;
use App\Modules\Services\Filament\Resources\Services\Pages\EditService;
use App\Modules\Services\Filament\Resources\Services\Pages\ListServices;
use App\Modules\Services\Filament\Resources\Services\Schemas\ServiceForm;
use App\Modules\Services\Filament\Resources\Services\Tables\ServicesTable;
use App\Modules\Services\Models\Service;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    public static function getModelLabel(): string
    {
        return 'Dịch vụ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Dịch vụ';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dịch vụ';
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
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
