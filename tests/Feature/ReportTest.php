<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create();
    }

    private function review(Book $book, int $rating, int $minutesAgo = 0): Review
    {
        return Review::factory()->for($this->user)->for($book)->create([
            'rating' => $rating,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    private function stats(): array
    {
        return $this->actingAs($this->user)->get('/reports')->assertOk()->viewData('stats');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/reports')->assertRedirect('/login');
    }

    public function test_empty_report(): void
    {
        $stats = $this->stats();

        $this->assertSame(['total_reviews' => 0, 'books_read' => 0, 'average_rating' => 0], $stats['summary']);
        $this->assertSame([0, 0, 0, 0, 0], $stats['rating_distribution']->all());
        $this->assertCount(0, $stats['top_rated_books']);
        $this->assertCount(0, $stats['genre_ratings']);
        $this->actingAs($this->user)->get('/reports')->assertSee('4星以上の書籍がありません');
    }

    public function test_summary_and_distribution_count_only_own_reviews(): void
    {
        $book = Book::factory()->create();
        $this->review($book, 5);
        $this->review($book, 3);
        $this->review(Book::factory()->create(), 1);
        Review::factory()->count(3)->create(['rating' => 5]); // 他人のレビュー

        $stats = $this->stats();

        $this->assertSame(3, $stats['summary']['total_reviews']);
        $this->assertSame(2, $stats['summary']['books_read']);
        $this->assertEquals(3, $stats['summary']['average_rating']);
        $this->assertSame([1, 0, 1, 0, 1], $stats['rating_distribution']->all());
    }

    public function test_top_rated_books_are_four_stars_or_more_top5(): void
    {
        $books = Book::factory()->count(7)->create();
        $this->review($books[0], 3);
        $this->review($books[1], 4, 1);
        $this->review($books[2], 5, 2);
        $this->review($books[3], 4, 3);
        $this->review($books[4], 5, 4);
        $this->review($books[5], 4, 5);
        $this->review($books[6], 4, 6);
        $this->review($books[1], 5, 7); // 同じ書籍の2件目は高い方を使う

        $top = $this->stats()['top_rated_books'];

        $this->assertSame(
            [[$books[1]->id, 5], [$books[2]->id, 5], [$books[4]->id, 5], [$books[3]->id, 4], [$books[5]->id, 4]],
            $top->map(fn ($book) => [$book['id'], $book['rating']])->all(),
        );
        $this->actingAs($this->user)->get('/reports')
            ->assertSee(route('books.show', $books[1]->id))
            ->assertSee($books[1]->title);
    }

    public function test_genre_ratings_are_sorted_by_average_then_count_top5(): void
    {
        $genres = Genre::factory()->count(6)->create();
        // ジャンル0: 平均5（1件）, ジャンル1: 平均5（2件）, ジャンル2〜5: 平均 4,3,2,1
        $this->review(Book::factory()->hasAttached($genres[0])->create(), 5);
        $this->review(Book::factory()->hasAttached([$genres[1], $genres[2]])->create(), 5);
        $this->review(Book::factory()->hasAttached($genres[1])->create(), 5);
        $this->review(Book::factory()->hasAttached($genres[2])->create(), 3);
        $this->review(Book::factory()->hasAttached($genres[3])->create(), 3);
        $this->review(Book::factory()->hasAttached($genres[4])->create(), 2);
        $this->review(Book::factory()->hasAttached($genres[5])->create(), 1);

        $ratings = $this->stats()['genre_ratings'];

        $this->assertSame(
            [[$genres[1]->id, 2, 5], [$genres[0]->id, 1, 5], [$genres[2]->id, 2, 4], [$genres[3]->id, 1, 3], [$genres[4]->id, 1, 2]],
            $ratings->map(fn ($genre) => [$genre['id'], $genre['count'], (int) $genre['average_rating']])->all(),
        );
        $this->actingAs($this->user)->get('/reports')->assertSee(route('genres.show', $genres[1]->id));
    }
}
