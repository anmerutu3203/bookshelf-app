<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\Api\V1\BookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Response;

class BookController extends Controller
{
    /**
     * 書籍一覧を keyword・ジャンル・per_page で絞り込み、ページネーションして返す。
     */
    public function index(BookIndexRequest $request): BookCollection
    {
        $books = Book::search($request->validated('keyword'), $request->validated('genre_id'))
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->paginate($request->validated('per_page', 20))
            ->withQueryString();

        return new BookCollection($books);
    }

    /**
     * 書籍詳細をジャンル・レビュー付きで返す。
     */
    public function show(Book $book): BookResource
    {
        $book->load(['genres', 'reviews.user'])->loadAvg('reviews', 'rating')->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍を登録して 201 で返す。
     */
    public function store(BookRequest $request): BookResource
    {
        // 登録者はトークンの持ち主にする
        $book = Book::createWithGenres(
            $request->user(),
            $request->safe()->except('genres'),
            $request->validated('genres'),
        );

        // wasRecentlyCreated が true のため 201 で返る
        return new BookResource($book->load('genres'));
    }

    /**
     * 書籍を更新する（所有者のみ）。
     */
    public function update(BookRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $book->updateWithGenres($request->safe()->except('genres'), $request->validated('genres'));

        return new BookResource($book->load('genres'));
    }

    /**
     * 書籍を削除して 204 を返す（所有者のみ）。
     */
    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        // レビュー・いいね・お気に入り・ジャンル紐付けは外部キーの cascade で同時に削除される
        $book->delete();

        return response()->noContent();
    }
}
