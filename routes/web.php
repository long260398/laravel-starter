<?php

use App\Http\Controllers\ContactMessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lien-he', [ContactMessageController::class, 'create'])->name('contact.create');
Route::post('/lien-he', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');
