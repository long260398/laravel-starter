<?php

namespace App\Modules\Testimonials\Filament\Resources\Testimonials\Pages;

use App\Modules\Testimonials\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestimonial extends CreateRecord
{
    protected static string $resource = TestimonialResource::class;
}
