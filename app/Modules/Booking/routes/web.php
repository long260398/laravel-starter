<?php

use App\Modules\Booking\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/booking', [BookingController::class, 'store'])
        ->middleware('throttle:booking')
        ->name('booking.store');
});
