<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_relations(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);
        $review = Review::factory()->for($book)->create();
        $fan = User::factory()->create();
        $fan->favoriteBooks()->attach($book);

        $this->assertInstanceOf(User::class, $book->user);
        $this->assertTrue($book->genres->contains($genre));
        $this->assertTrue($book->reviews->contains($review));
        $this->assertTrue($book->favoritedByUsers->contains($fan));
    }

    public function test_create_with_genres_saves_book_and_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $attributes = Book::factory()->make()->only(['title', 'author', 'isbn', 'published_date']);

        $book = Book::createWithGenres($user, $attributes, $genres->pluck('id')->all());

        $this->assertSame($user->id, $book->user_id);
        $this->assertCount(2, $book->genres);
    }

    public function test_update_with_genres_replaces_genres(): void
    {
        $book = Book::factory()->create();
        $book->genres()->attach(Genre::factory()->create());
        $newGenre = Genre::factory()->create();

        $book->updateWithGenres(['title' => '新タイトル'], [$newGenre->id]);

        $this->assertSame('新タイトル', $book->fresh()->title);
        $this->assertSame([$newGenre->id], $book->genres()->pluck('genres.id')->all());
    }
}
