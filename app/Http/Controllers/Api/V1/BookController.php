<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\Api\V1\BookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\User;

class BookController extends Controller
{
    public function index(BookIndexRequest $request)
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

    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user'])->loadAvg('reviews', 'rating')->loadCount('reviews');

        return new BookResource($book);
    }

    public function store(BookRequest $request)
    {
        $book = Book::createWithGenres(
            User::find($request->validated('user_id')),
            $request->safe()->except(['genres', 'user_id']),
            $request->validated('genres'),
        );

        // wasRecentlyCreated が true のため 201 で返る
        return new BookResource($book->load('genres'));
    }

    public function update(BookRequest $request, Book $book)
    {
        $book->updateWithGenres($request->safe()->except('genres'), $request->validated('genres'));

        return new BookResource($book->load('genres'));
    }

    public function destroy(Book $book)
    {
        // レビュー・いいね・お気に入り・ジャンル紐付けは外部キーの cascade で同時に削除される
        $book->delete();

        return response()->noContent();
    }
}
