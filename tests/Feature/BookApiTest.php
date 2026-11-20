<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return [
            'user_id' => User::factory()->create()->id,
            'title' => 'API書籍',
            'author' => 'API著者',
            'isbn' => '9784000000001',
            'published_date' => '2020-01-01',
            'description' => '説明',
            'image_url' => 'https://example.com/book.png',
            'genres' => [Genre::factory()->create()->id],
            ...$overrides,
        ];
    }

    public function test_index_returns_books_with_genres_average_and_review_count(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);
        $book = Book::factory()->create();
        $book->genres()->attach($genre);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);
        Book::factory()->create();

        $response = $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta', ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 2])
            ->assertJsonMissingPath('links');

        $item = collect($response->json('data'))->firstWhere('id', $book->id);
        $this->assertSame(
            ['id', 'title', 'author', 'isbn', 'published_date', 'genres', 'average_rating', 'review_count'],
            array_keys($item),
        );
        $this->assertSame([['id' => $genre->id, 'name' => '小説']], $item['genres']);
        $this->assertEquals(4.5, $item['average_rating']);
        $this->assertSame(2, $item['review_count']);
        $this->assertNull(collect($response->json('data'))->firstWhere('id', '!=', $book->id)['average_rating']);
    }

    public function test_index_filters_by_keyword_and_genre(): void
    {
        $genre = Genre::factory()->create();
        Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Boswell'])->genres()->attach($genre);
        Book::factory()->create(['title' => '坊っちゃん', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => 'コードの本', 'author' => '別の著者']);

        $this->getJson('/api/v1/books?keyword=漱石')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', '坊っちゃん');
        $this->getJson('/api/v1/books?keyword=コード')->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/books?keyword=コード&genre_id={$genre->id}")
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'リーダブルコード');
    }

    public function test_index_paginates_with_per_page(): void
    {
        Book::factory()->count(22)->create();

        $this->getJson('/api/v1/books')->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 22);
        $this->getJson('/api/v1/books?per_page=5&page=5')
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.current_page', 5);
    }

    public function test_index_validates_query_in_japanese(): void
    {
        $this->getJson('/api/v1/books?genre_id=999&page=0&per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id' => '指定されたジャンルが存在しません。',
                'page' => 'ページ番号は1以上で入力してください。',
                'per_page' => '1ページあたりの件数は1〜100で入力してください。',
            ])
            ->assertJsonPath('message', '指定されたジャンルが存在しません。 （他 2 件のエラー）');
        $this->getJson('/api/v1/books?genre_id=abc&per_page=abc')
            ->assertJsonValidationErrors([
                'genre_id' => 'ジャンルIDは整数で入力してください。',
                'per_page' => '1ページあたりの件数は整数で入力してください。',
            ]);
    }

    public function test_show_returns_book_with_genres_and_reviews(): void
    {
        $book = Book::factory()->create();
        $book->genres()->attach(Genre::factory()->create(['name' => '技術書']));
        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => User::factory()->create(['name' => '山田太郎'])->id,
            'rating' => 5,
            'comment' => '名著',
        ]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.title', $book->title)
            ->assertJsonPath('data.genres.0.name', '技術書')
            ->assertJsonPath('data.reviews.0.user_name', '山田太郎')
            ->assertJsonPath('data.reviews.0.rating', 5)
            ->assertJsonPath('data.reviews.0.comment', '名著')
            ->assertJsonPath('data.reviews.0.created_at', $review->created_at->toJSON());
    }

    public function test_missing_book_returns_json_404(): void
    {
        foreach (['get', 'put', 'delete'] as $method) {
            // Accept ヘッダーなしでも JSON で返ること
            $this->$method('/api/v1/books/999')
                ->assertNotFound()
                ->assertExactJson(['error' => '書籍が見つかりませんでした。']);
        }
        $this->getJson('/api/v1/unknown')
            ->assertNotFound()
            ->assertExactJson(['error' => '指定されたデータが見つかりません。']);
    }

    public function test_store_creates_book_with_genres_and_returns_201(): void
    {
        $payload = $this->validPayload();

        $this->postJson('/api/v1/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', 'API書籍')
            ->assertJsonPath('data.user_id', $payload['user_id'])
            ->assertJsonPath('data.genres.0.id', $payload['genres'][0]);

        $book = Book::firstWhere('isbn', '9784000000001');
        $this->assertSame($payload['user_id'], $book->user_id);
        $this->assertSame($payload['genres'], $book->genres->pluck('id')->all());
    }

    public function test_store_validates_in_japanese(): void
    {
        Book::factory()->create(['isbn' => '9784000000001']);

        $this->post('/api/v1/books', $this->validPayload(['user_id' => 999, 'title' => '', 'genres' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id' => '指定された登録者は存在しません。',
                'title' => 'タイトルは必須です。',
                'isbn' => 'このISBNは既に登録されています。',
                'genres' => 'ジャンルは1つ以上選択してください。',
            ]);
        $this->postJson('/api/v1/books', $this->validPayload(['user_id' => null]))
            ->assertJsonValidationErrors(['user_id' => '登録者IDは必須です。']);
        $this->postJson('/api/v1/books', $this->validPayload(['user_id' => 'abc']))
            ->assertJsonValidationErrors(['user_id' => '登録者IDは整数で入力してください。']);
        $this->assertDatabaseCount('books', 1);
    }

    public function test_update_changes_book_and_keeps_own_isbn(): void
    {
        $book = Book::factory()->create(['isbn' => '9784000000001']);
        $payload = $this->validPayload(['title' => '更新後']);

        $this->putJson("/api/v1/books/{$book->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.title', '更新後')
            ->assertJsonPath('data.genres.0.id', $payload['genres'][0]);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後', 'user_id' => $payload['user_id']]);
    }

    public function test_update_rejects_other_books_isbn(): void
    {
        Book::factory()->create(['isbn' => '9784000000001']);
        $book = Book::factory()->create();

        $this->putJson("/api/v1/books/{$book->id}", $this->validPayload())
            ->assertJsonValidationErrors(['isbn' => 'このISBNは既に登録されています。']);
    }

    public function test_destroy_deletes_book_and_returns_204(): void
    {
        $book = Book::factory()->create();
        $book->genres()->attach(Genre::factory()->create());
        Review::factory()->create(['book_id' => $book->id]);

        $this->deleteJson("/api/v1/books/{$book->id}")->assertNoContent();

        $this->assertModelMissing($book);
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('book_genre', 0);
    }
}
