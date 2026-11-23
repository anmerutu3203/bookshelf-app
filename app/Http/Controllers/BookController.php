<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Http\Requests\BookSearchRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を検索条件・並び順に従って10件ずつ表示する。
     */
    public function index(BookSearchRequest $request): View
    {
        $books = Book::search($request->validated('keyword'), $request->validated('genre'))
            ->sortBy($request->validated('sort'))
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->paginate(10)
            ->withQueryString();
        $genres = Genre::orderBy('id')->get();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍詳細をジャンル・レビュー付きで表示する。
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'reviews.user', 'reviews.likedByUsers']);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍登録フォームを表示する。
     */
    public function create(): View
    {
        $genres = Genre::orderBy('id')->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録し、ジャンルを紐付ける。
     */
    public function store(BookRequest $request): RedirectResponse
    {
        $book = Book::createWithGenres(
            $request->user(),
            $request->safe()->except('genres'),
            $request->validated('genres'),
        );

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました');
    }

    /**
     * 書籍編集フォームを表示する（作成者のみ）。
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $book->load('genres');
        $genres = Genre::orderBy('id')->get();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍とジャンルの紐付けを更新する（作成者のみ）。
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $book->updateWithGenres($request->safe()->except('genres'), $request->validated('genres'));

        return redirect()->route('books.show', $book)->with('success', '書籍情報を編集しました');
    }

    /**
     * 書籍を削除する（作成者のみ）。
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        // レビュー・いいね・お気に入り・ジャンル紐付けは外部キーの cascadeOnDelete で同時に削除される
        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました');
    }
}
