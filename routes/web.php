<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookIsbnController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
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
    Route::get('/books/isbn/{isbn}', BookIsbnController::class)->name('books.isbn');

    Route::resource('genres', GenreController::class);

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::resource('reviews', ReviewController::class)->only(['edit', 'update', 'destroy']);
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])->name('reviews.like');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::resource('reading-plans', ReadingPlanController::class)->except('show')->parameters(['reading-plans' => 'plan']);
    Route::post('/reading-plans/{plan}/complete', [ReadingPlanController::class, 'complete'])->name('reading-plans.complete');

    // ponytail: ナビゲーションのリンク先だけ用意した仮ルート。#20 で本実装に置き換える
    foreach (['notifications.index' => '/notifications'] as $name => $uri) {
        Route::get($uri, fn () => redirect()->route('books.index')->with('error', 'この機能は準備中です。'))->name($name);
    }
});

// /books/create より後に定義し、{book} が create にマッチしないようにする
Route::resource('books', BookController::class)->only(['index', 'show']);
