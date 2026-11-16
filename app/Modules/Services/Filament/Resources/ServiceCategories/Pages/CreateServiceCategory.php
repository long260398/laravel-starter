<?php

namespace App\Modules\Services\Filament\Resources\ServiceCategories\Pages;

use App\Modules\Services\Filament\Resources\ServiceCategories\ServiceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCategory extends CreateRecord
{
    protected static string $resource = ServiceCategoryResource::class;
}
