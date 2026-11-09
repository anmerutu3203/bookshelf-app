<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Genre;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user', 'reviews.likedByUsers']);

        return view('books.show', compact('book'));
    }

    public function create()
    {
        $genres = Genre::orderBy('id')->get();

        return view('books.create', compact('genres'));
    }

    public function store(BookRequest $request)
    {
        $book = Book::createWithGenres(
            $request->user(),
            $request->safe()->except('genres'),
            $request->validated('genres'),
        );

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました');
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $book->load('genres');
        $genres = Genre::orderBy('id')->get();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(BookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $book->updateWithGenres($request->safe()->except('genres'), $request->validated('genres'));

        return redirect()->route('books.show', $book)->with('success', '書籍情報を編集しました');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        // レビュー・いいね・お気に入り・ジャンル紐付けは外部キーの cascadeOnDelete で同時に削除される
        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました');
    }
}
