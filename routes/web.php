<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// 公開ページ
Route::get('/', [BookController::class, 'index']);
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

// 認証必須ページ
Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)->except(['index', 'show']);

    Route::resource('genres', GenreController::class);

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::resource('reviews', ReviewController::class)->only(['edit', 'update', 'destroy']);
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])->name('reviews.like');
});

// /books/create より後に定義し、{book} が create にマッチしないようにする
Route::resource('books', BookController::class)->only(['index', 'show']);
