<?php

namespace App\Modules\Services\Filament\Resources\Services\Pages;

use App\Modules\Services\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;
}
