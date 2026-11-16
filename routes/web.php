<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/tin-tuc', [PostController::class, 'index'])->name('posts.index');
Route::get('/tin-tuc/{slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/lien-he', [ContactMessageController::class, 'create'])->name('contact.create');
Route::post('/lien-he', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    return response()->view('robots', [], 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// Phải đăng ký cuối cùng: khớp mọi slug chưa có route cụ thể nào xử lý.
Route::get('/{slug}', [StaticPageController::class, 'show'])->name('static-pages.show');
