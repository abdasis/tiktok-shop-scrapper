<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TIktokController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
   return Inertia::render('Welcome', [
	  'canLogin' => Route::has('login'),
	  'canRegister' => Route::has('register'),
	  'laravelVersion' => Application::VERSION,
	  'phpVersion' => PHP_VERSION,
   ]);
});
Route::group(['prefix' => 'tiktok'], function () {
   Route::get('get-product', [TIktokController::class, 'getProduct'])->name('tiktok.get-product');
   Route::post('get-product', [TIktokController::class, 'productScrapper'])->name('tiktok.product-scrapper');
});

Route::get('/dashboard', function () {
   return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
   Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
   Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
   Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
