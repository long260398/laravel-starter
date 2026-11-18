<?php

namespace App\Modules\Faq\Filament\Resources\Faqs\Pages;

use App\Modules\Faq\Filament\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;
}
