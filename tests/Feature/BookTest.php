<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function bookData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => '読みやすいコードの書き方',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [Genre::factory()->create()->id],
        ], $overrides);
    }

    // 一覧

    public function test_index_is_shown_at_root_and_books(): void
    {
        $book = Book::factory()->create();

        $this->get('/')->assertOk()->assertViewIs('books.index')->assertSee($book->title);
        $this->get('/books')->assertOk()->assertViewIs('books.index')->assertSee($book->title);
    }

    public function test_index_paginates_ten_books_in_latest_order(): void
    {
        foreach (range(1, 11) as $day) {
            Book::factory()->create(['title' => "書籍{$day}", 'created_at' => now()->subDays(12 - $day)]);
        }

        $this->get('/books')->assertViewHas('books', function ($books) {
            return $books->count() === 10 && $books->first()->title === '書籍11';
        });
        $this->get('/books?page=2')->assertViewHas('books', function ($books) {
            return $books->count() === 1 && $books->first()->title === '書籍1';
        });
    }

    public function test_index_shows_genres(): void
    {
        $genre = Genre::factory()->create(['name' => 'テストジャンル']);
        Book::factory()->create()->genres()->attach($genre);

        $this->get('/books')->assertSee('テストジャンル');
    }

    // 詳細

    public function test_show_displays_book_with_genres_and_reviews(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => 'テストジャンル']);
        $book->genres()->attach($genre);
        Review::factory()->for($book)->create(['comment' => 'とても良い本でした']);

        $this->get("/books/{$book->id}")
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee('テストジャンル')
            ->assertSee('とても良い本でした');
    }

    public function test_show_returns_404_for_missing_book(): void
    {
        $this->get('/books/999')->assertNotFound();
    }

    // 登録

    public function test_guest_is_redirected_to_login_from_create_and_store(): void
    {
        $this->get('/books/create')->assertRedirect('/login');
        $this->post('/books', $this->bookData())->assertRedirect('/login');
        $this->assertDatabaseCount('books', 0);
    }

    public function test_create_page_shows_all_genres(): void
    {
        Genre::factory()->create(['name' => '小説']);
        Genre::factory()->create(['name' => '技術書']);

        $this->actingAs(User::factory()->create())
            ->get('/books/create')
            ->assertOk()
            ->assertSee('小説')
            ->assertSee('技術書');
    }

    public function test_user_can_store_book_with_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $response = $this->actingAs($user)->post('/books', $this->bookData(['genres' => $genres->pluck('id')->all()]));

        $book = Book::where('isbn', '9784873115658')->firstOrFail();
        $response->assertRedirect("/books/{$book->id}")->assertSessionHas('success', '書籍を登録しました');
        $this->assertSame($user->id, $book->user_id);
        $this->assertEqualsCanonicalizing($genres->pluck('id')->all(), $book->genres->pluck('id')->all());
    }

    public function test_store_requires_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/books', [])
            ->assertSessionHasErrors([
                'title' => 'タイトルは必須です',
                'author' => '著者は必須です',
                'isbn' => 'ISBNは必須です',
                'published_date' => '出版日は必須です',
                'genres' => 'ジャンルは1つ以上選択してください',
            ]);
    }

    public function test_store_rejects_invalid_isbn(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/books', $this->bookData(['isbn' => '978487311565']))
            ->assertSessionHasErrors(['isbn' => 'ISBNは13桁の数字で入力してください']);
        $this->actingAs($user)->post('/books', $this->bookData(['isbn' => '978-4873115658']))
            ->assertSessionHasErrors(['isbn' => 'ISBNは13桁の数字で入力してください']);
    }

    public function test_store_rejects_duplicate_isbn(): void
    {
        Book::factory()->create(['isbn' => '9784873115658']);

        $this->actingAs(User::factory()->create())
            ->post('/books', $this->bookData())
            ->assertSessionHasErrors(['isbn' => 'このISBNは既に登録されています']);
    }

    public function test_store_rejects_invalid_date_url_and_genre(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/books', $this->bookData([
                'published_date' => 'not-a-date',
                'image_url' => 'not-a-url',
                'genres' => [999],
            ]))
            ->assertSessionHasErrors([
                'published_date' => '出版日は有効な日付で入力してください',
                'image_url' => '画像URLはURL形式で入力してください',
                'genres.0' => '選択されたジャンルが存在しません',
            ]);
    }

    public function test_store_rejects_too_long_values(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/books', $this->bookData([
                'title' => str_repeat('あ', 256),
                'author' => str_repeat('あ', 256),
                'description' => str_repeat('あ', 1001),
            ]))
            ->assertSessionHasErrors([
                'title' => 'タイトルは255文字以内で入力してください',
                'author' => '著者は255文字以内で入力してください',
                'description' => '説明は1000文字以内で入力してください',
            ]);
    }

    public function test_store_allows_empty_description_and_image_url(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/books', $this->bookData(['description' => '', 'image_url' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', ['isbn' => '9784873115658', 'description' => null, 'image_url' => null]);
    }

    // 編集・更新

    public function test_owner_can_view_edit_page(): void
    {
        $book = Book::factory()->create();

        $this->actingAs($book->user)->get("/books/{$book->id}/edit")
            ->assertOk()
            ->assertViewIs('books.edit')
            ->assertSee($book->title);
    }

    public function test_owner_can_update_book_and_genres(): void
    {
        $book = Book::factory()->create(['isbn' => '9784873115658']);
        $book->genres()->attach(Genre::factory()->create());
        $newGenre = Genre::factory()->create();

        $this->actingAs($book->user)
            ->put("/books/{$book->id}", $this->bookData(['title' => '更新後のタイトル', 'genres' => [$newGenre->id]]))
            ->assertRedirect("/books/{$book->id}")
            ->assertSessionHas('success', '書籍を更新しました');

        $book->refresh();
        $this->assertSame('更新後のタイトル', $book->title);
        $this->assertSame([$newGenre->id], $book->genres->pluck('id')->all());
    }

    public function test_update_keeps_own_isbn_without_unique_error(): void
    {
        $book = Book::factory()->create(['isbn' => '9784873115658']);

        $this->actingAs($book->user)
            ->put("/books/{$book->id}", $this->bookData())
            ->assertSessionHasNoErrors();
    }

    public function test_update_rejects_other_books_isbn(): void
    {
        Book::factory()->create(['isbn' => '9784101010014']);
        $book = Book::factory()->create(['isbn' => '9784873115658']);

        $this->actingAs($book->user)
            ->put("/books/{$book->id}", $this->bookData(['isbn' => '9784101010014']))
            ->assertSessionHasErrors(['isbn' => 'このISBNは既に登録されています']);
    }

    public function test_other_user_cannot_edit_or_update_book(): void
    {
        $book = Book::factory()->create(['title' => '元のタイトル']);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->get("/books/{$book->id}/edit")->assertForbidden();
        $this->actingAs($otherUser)
            ->put("/books/{$book->id}", $this->bookData(['title' => '書き換え']))
            ->assertForbidden();
        $this->assertSame('元のタイトル', $book->fresh()->title);
    }

    public function test_guest_is_redirected_to_login_from_edit(): void
    {
        $book = Book::factory()->create();

        $this->get("/books/{$book->id}/edit")->assertRedirect('/login');
    }

    // 削除

    public function test_owner_can_delete_book_with_related_data(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);
        $review = Review::factory()->for($book)->create();
        $user = User::factory()->create();
        $user->favoriteBooks()->attach($book);
        $user->likedReviews()->attach($review);

        $this->actingAs($book->user)
            ->delete("/books/{$book->id}")
            ->assertRedirect('/books')
            ->assertSessionHas('success', '書籍を削除しました');

        $this->assertModelMissing($book);
        $this->assertModelMissing($review);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
        $this->assertModelExists($genre);
    }

    public function test_other_user_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->create())->delete("/books/{$book->id}")->assertForbidden();
        $this->assertModelExists($book);
    }

    // 詳細画面の編集・削除ボタン

    public function test_edit_and_delete_buttons_are_shown_only_to_owner(): void
    {
        $book = Book::factory()->create();

        $this->actingAs($book->user)->get("/books/{$book->id}")->assertSee("/books/{$book->id}/edit");
        $this->actingAs(User::factory()->create())->get("/books/{$book->id}")->assertDontSee("/books/{$book->id}/edit");
    }
}
