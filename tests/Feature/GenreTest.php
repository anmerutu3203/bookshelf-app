<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $genre = Genre::factory()->create();

        $this->get('/genres')->assertRedirect('/login');
        $this->get("/genres/{$genre->id}")->assertRedirect('/login');
        $this->get('/genres/create')->assertRedirect('/login');
        $this->get("/genres/{$genre->id}/edit")->assertRedirect('/login');
        $this->post('/genres', ['name' => '新ジャンル'])->assertRedirect('/login');
        $this->put("/genres/{$genre->id}", ['name' => '新ジャンル'])->assertRedirect('/login');
        $this->delete("/genres/{$genre->id}")->assertRedirect('/login');
        $this->assertDatabaseMissing('genres', ['name' => '新ジャンル']);
    }

    // 一覧・詳細

    public function test_index_shows_genres_with_book_count(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);
        Book::factory()->count(2)->create()->each(fn ($book) => $book->genres()->attach($genre));

        $this->actingAs($this->user)->get('/genres')
            ->assertOk()
            ->assertViewIs('genres.index')
            ->assertSee('小説')
            ->assertSee('2冊');
    }

    public function test_show_paginates_ten_books_of_genre_in_latest_order(): void
    {
        $genre = Genre::factory()->create();
        foreach (range(1, 11) as $day) {
            Book::factory()->create(['title' => "書籍{$day}", 'created_at' => now()->subDays(12 - $day)])
                ->genres()->attach($genre);
        }
        $otherBook = Book::factory()->create(['title' => '他ジャンルの書籍']);

        $this->actingAs($this->user)->get("/genres/{$genre->id}")
            ->assertOk()
            ->assertViewIs('genres.show')
            ->assertDontSee('他ジャンルの書籍')
            ->assertViewHas('books', fn ($books) => $books->count() === 10 && $books->first()->title === '書籍11');
        $this->actingAs($this->user)->get("/genres/{$genre->id}?page=2")
            ->assertViewHas('books', fn ($books) => $books->count() === 1 && $books->first()->title === '書籍1');
    }

    public function test_show_returns_404_for_missing_genre(): void
    {
        $this->actingAs($this->user)->get('/genres/999')->assertNotFound();
    }

    // 登録

    public function test_create_page_is_shown(): void
    {
        $this->actingAs($this->user)->get('/genres/create')->assertOk()->assertViewIs('genres.create');
    }

    public function test_user_can_store_genre(): void
    {
        $this->actingAs($this->user)->post('/genres', ['name' => '新ジャンル'])
            ->assertRedirect('/genres')
            ->assertSessionHas('success', 'ジャンルを作成しました。');

        $this->assertDatabaseHas('genres', ['name' => '新ジャンル']);
    }

    public function test_store_validates_name(): void
    {
        Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user)->post('/genres', ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);
        $this->actingAs($this->user)->post('/genres', ['name' => '小説'])
            ->assertSessionHasErrors(['name' => 'そのジャンル名は既に使用されています。']);
        $this->actingAs($this->user)->post('/genres', ['name' => ['配列']])
            ->assertSessionHasErrors(['name' => 'ジャンル名は文字列で入力してください。']);
        $this->actingAs($this->user)->post('/genres', ['name' => str_repeat('あ', 256)])
            ->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);
    }

    // 編集

    public function test_edit_page_is_shown(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user)->get("/genres/{$genre->id}/edit")
            ->assertOk()
            ->assertViewIs('genres.edit')
            ->assertSee('小説');
    }

    public function test_user_can_update_genre(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);

        $this->actingAs($this->user)->put("/genres/{$genre->id}", ['name' => '文学'])
            ->assertRedirect('/genres')
            ->assertSessionHas('success', 'ジャンルを更新しました');

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '文学']);
    }

    public function test_update_keeps_own_name_and_rejects_other_name(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);
        Genre::factory()->create(['name' => '歴史']);

        $this->actingAs($this->user)->put("/genres/{$genre->id}", ['name' => '小説'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->user)->put("/genres/{$genre->id}", ['name' => '歴史'])
            ->assertSessionHasErrors(['name' => 'そのジャンル名は既に使用されています。']);
    }

    // 削除

    public function test_user_can_delete_genre_without_books(): void
    {
        $genre = Genre::factory()->create();

        $this->actingAs($this->user)->delete("/genres/{$genre->id}")
            ->assertRedirect('/genres')
            ->assertSessionHas('success', 'ジャンルを削除しました');

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $genre = Genre::factory()->create();
        Book::factory()->create()->genres()->attach($genre);

        $this->actingAs($this->user)->delete("/genres/{$genre->id}")
            ->assertRedirect('/genres')
            ->assertSessionHas('error', 'このジャンルには書籍が紐づいているため削除できません');

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
}
