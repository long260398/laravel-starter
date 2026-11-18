<?php

namespace App\Modules\Testimonials\Filament\Resources\Testimonials;

use App\Enums\NavigationGroup;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Schemas\TestimonialForm;
use App\Modules\Testimonials\Filament\Resources\Testimonials\Tables\TestimonialsTable;
use App\Modules\Testimonials\Models\Testimonial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $recordTitleAttribute = 'customer_name';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    public static function getModelLabel(): string
    {
        return 'Cảm nhận khách hàng';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Cảm nhận khách hàng';
    }

    public static function getNavigationLabel(): string
    {
        return 'Cảm nhận khách hàng';
    }

    public static function form(Schema $schema): Schema
    {
        return TestimonialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestimonialsTable::configure($table);
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
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
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
