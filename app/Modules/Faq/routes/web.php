<?php

use App\Modules\Faq\Http\Controllers\FaqController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
});
