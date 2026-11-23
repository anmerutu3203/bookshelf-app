<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入りに追加した書籍を新しい順に10件ずつ表示する。
     */
    public function index(Request $request): View
    {
        // お気に入りに追加した日時が新しい順
        $books = $request->user()->favoriteBooks()->latest('favorites.created_at')->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * 書籍のお気に入りを追加・解除する。
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $request->user()->toggleFavorite($book);

        return back();
    }
}
