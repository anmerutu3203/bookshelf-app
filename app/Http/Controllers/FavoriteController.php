<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        // お気に入りに追加した日時が新しい順
        $books = $request->user()->favoriteBooks()->latest('favorites.created_at')->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Request $request, Book $book)
    {
        $added = $request->user()->toggleFavorite($book);

        return back()->with('success', $added ? 'お気に入りに追加しました' : 'お気に入りから削除しました');
    }
}
