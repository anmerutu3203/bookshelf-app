<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Book $book)
    {
        $request->user()->reviews()->create([...$request->validated(), 'book_id' => $book->id]);

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました');
    }

    public function edit(Review $review)
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    public function update(ReviewRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを編集しました');
    }

    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        // いいねは外部キーの cascadeOnDelete で同時に削除される
        $review->delete();

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを削除しました');
    }
}
