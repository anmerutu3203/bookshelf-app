<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function bookWithRatings(string $title, array $ratings): Book
    {
        $book = Book::factory()->create(['title' => $title]);
        foreach ($ratings as $rating) {
            Review::factory()->create(['book_id' => $book->id, 'rating' => $rating]);
        }

        return $book;
    }

    public function test_guest_can_view_ranking_sorted_by_average_rating(): void
    {
        $this->bookWithRatings('平均3', [3]);
        $this->bookWithRatings('平均5', [5, 5]);
        $this->bookWithRatings('平均4.5', [4, 5]);
        Book::factory()->create(['title' => 'レビューなし']);

        $this->get('/ranking')
            ->assertOk()
            ->assertViewIs('ranking.index')
            ->assertSeeInOrder(['平均5', '平均4.5', '平均3'])
            ->assertSee('4.50')
            ->assertSee('(2件のレビュー)')
            ->assertDontSee('レビューなし');
    }

    public function test_same_average_is_ordered_by_review_count(): void
    {
        $this->bookWithRatings('1件', [4]);
        $this->bookWithRatings('3件', [4, 4, 4]);

        $this->get('/ranking')->assertSeeInOrder(['3件', '1件']);
    }

    public function test_ranking_shows_top_ten_only(): void
    {
        foreach (range(1, 11) as $number) {
            $this->bookWithRatings("書籍{$number}", [$number <= 10 ? 5 : 1]);
        }

        $this->get('/ranking')
            ->assertViewHas('rankedBooks', fn ($books) => $books->count() === 10)
            ->assertDontSee('書籍11');
    }

    public function test_empty_message_when_no_reviews(): void
    {
        Book::factory()->create();

        $this->get('/ranking')->assertSee('まだレビューが投稿された書籍がありません。');
    }
}
