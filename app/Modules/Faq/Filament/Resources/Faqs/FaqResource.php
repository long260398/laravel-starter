<?php

namespace App\Modules\Faq\Filament\Resources\Faqs;

use App\Enums\NavigationGroup;
use App\Modules\Faq\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Modules\Faq\Filament\Resources\Faqs\Pages\EditFaq;
use App\Modules\Faq\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Modules\Faq\Filament\Resources\Faqs\Schemas\FaqForm;
use App\Modules\Faq\Filament\Resources\Faqs\Tables\FaqsTable;
use App\Modules\Faq\Models\Faq;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $recordTitleAttribute = 'question';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    public static function getModelLabel(): string
    {
        return 'Câu hỏi thường gặp';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Câu hỏi thường gặp';
    }

    public static function getNavigationLabel(): string
    {
        return 'Câu hỏi thường gặp';
    }

    public static function form(Schema $schema): Schema
    {
        return FaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqsTable::configure($table);
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
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
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
