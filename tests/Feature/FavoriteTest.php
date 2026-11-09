<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $book = Book::factory()->create();

        $this->get('/favorites')->assertRedirect('/login');
        $this->post("/books/{$book->id}/favorites")->assertRedirect('/login');
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_toggle_adds_and_removes_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->from("/books/{$book->id}")->post("/books/{$book->id}/favorites")
            ->assertRedirect("/books/{$book->id}")
            ->assertSessionHas('success', 'お気に入りに追加しました');
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        $this->actingAs($user)->from('/favorites')->post("/books/{$book->id}/favorites")
            ->assertRedirect('/favorites')
            ->assertSessionHas('success', 'お気に入りから削除しました');
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    public function test_toggle_returns_404_for_missing_book(): void
    {
        $this->actingAs(User::factory()->create())->post('/books/999/favorites')->assertNotFound();
    }

    public function test_index_shows_only_own_favorites_ten_per_page_in_latest_order(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 11) as $day) {
            $user->favoriteBooks()->attach(Book::factory()->create(['title' => "書籍{$day}"]), ['created_at' => now()->subDays(12 - $day)]);
        }
        User::factory()->create()->favoriteBooks()->attach(Book::factory()->create(['title' => '他人のお気に入り']));

        $this->actingAs($user)->get('/favorites')
            ->assertOk()
            ->assertViewIs('favorites.index')
            ->assertDontSee('他人のお気に入り')
            ->assertViewHas('books', fn ($books) => $books->count() === 10 && $books->first()->title === '書籍11');
        $this->actingAs($user)->get('/favorites?page=2')
            ->assertViewHas('books', fn ($books) => $books->count() === 1 && $books->first()->title === '書籍1');
    }

    public function test_index_shows_empty_message(): void
    {
        $this->actingAs(User::factory()->create())->get('/favorites')
            ->assertOk()
            ->assertSee('お気に入りに登録された書籍はありません。');
    }
}
