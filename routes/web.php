<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/blog', [PostController::class, 'index'])->name('posts.index');
Route::get('/blog/search', [PostController::class, 'search'])
    ->middleware('throttle:post-search')
    ->name('posts.search');
Route::get('/blog/category/{slug}', [PostController::class, 'category'])->name('posts.category-show');
Route::get('/blog/tag/{slug}', [PostController::class, 'tag'])->name('posts.tag-show');
// Phải đứng sau 3 route cụ thể phía trên — nếu không, slug bài viết trùng
// "search"/"category"/"tag" sẽ bị route động {slug} này nuốt mất đường dẫn
// tương ứng (xem rule not_in trên field slug của PostForm).
Route::get('/blog/{slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/contact', [ContactMessageController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    return response()->view('robots', [], 200, ['Content-Type' => 'text/plain']);
})->name('robots');

// Route::fallback() chỉ được Laravel xét tới khi không route nào khác khớp,
// bất kể thứ tự đăng ký (kể cả route của module nạp sau file này).
Route::fallback([StaticPageController::class, 'show'])->name('static-pages.show');
