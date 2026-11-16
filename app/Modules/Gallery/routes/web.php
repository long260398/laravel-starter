<?php

use App\Modules\Gallery\Http\Controllers\GalleryController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::get('/gallery/{slug}', [GalleryController::class, 'show'])->name('gallery.show');
});
