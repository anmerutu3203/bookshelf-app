<?php

namespace App\Services;

use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

class ReadingReportService
{
    /**
     * マイ読書レポートの4種類の統計を、ユーザーのレビューから集計する。
     *
     * @return array{summary: array, rating_distribution: Collection, top_rated_books: Collection, genre_ratings: Collection}
     */
    public function build(User $user): array
    {
        $reviews = $user->reviews()->with('book.genres')->latest()->get();

        return [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews->unique('book_id')->count(),
                'average_rating' => $reviews->avg('rating') ?? 0,
            ],
            'rating_distribution' => $this->ratingDistribution($reviews),
            'top_rated_books' => $this->topRatedBooks($reviews),
            'genre_ratings' => $this->genreRatings($reviews),
        ];
    }

    /**
     * 星1〜5ごとの件数。添字0が星1。
     */
    private function ratingDistribution(Collection $reviews): Collection
    {
        $counts = $reviews->countBy('rating');

        return collect(range(1, 5))->map(fn (int $rating) => $counts->get($rating, 0));
    }

    /**
     * 星4以上をつけた書籍を評価の高い順に5件。同じ書籍に複数レビューがある場合は最も高い評価を使う。
     * 同じ評価なら最近レビューした順。
     */
    private function topRatedBooks(Collection $reviews): Collection
    {
        return $reviews
            ->filter(fn (Review $review) => $review->rating >= 4)
            ->groupBy('book_id')
            ->map(fn (Collection $bookReviews) => [
                'id' => $bookReviews->first()->book->id,
                'title' => $bookReviews->first()->book->title,
                'author' => $bookReviews->first()->book->author,
                'rating' => $bookReviews->max('rating'),
            ])
            // groupBy は最近レビューした書籍から並ぶため、安定ソートで同評価の順を保つ
            ->sortByDesc('rating')
            ->take(5)
            ->values();
    }

    /**
     * ジャンルごとの平均評価とレビュー件数を、平均評価の高い順に5件。同じ平均なら件数が多い順。
     * 複数ジャンルの書籍のレビューは、それぞれのジャンルに数える。
     */
    private function genreRatings(Collection $reviews): Collection
    {
        return $reviews
            ->flatMap(fn (Review $review) => $review->book->genres->map(fn (Genre $genre) => [
                'genre' => $genre,
                'rating' => $review->rating,
            ]))
            ->groupBy('genre.id')
            ->map(fn (Collection $items) => [
                'id' => $items->first()['genre']->id,
                'name' => $items->first()['genre']->name,
                'count' => $items->count(),
                'average_rating' => $items->avg('rating'),
            ])
            ->sortBy([['average_rating', 'desc'], ['count', 'desc']])
            ->take(5)
            ->values();
    }
}
