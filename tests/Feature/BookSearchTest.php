<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function titles(string $query): array
    {
        return $this->get("/books{$query}")->assertOk()->viewData('books')->pluck('title')->all();
    }

    public function test_keyword_matches_title_or_author_partially(): void
    {
        Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);
        Book::factory()->create(['title' => '坊っちゃん', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => '火花', 'author' => '又吉直樹']);

        $this->assertSame(['リーダブルコード'], $this->titles('?keyword=ダブル'));
        $this->assertSame(['坊っちゃん'], $this->titles('?keyword=漱石'));
        $this->assertSame([], $this->titles('?keyword=存在しない'));
        $this->get('/books?keyword=漱石')->assertSee('検索結果: 1件');
    }

    public function test_genre_filters_books_and_combines_with_keyword(): void
    {
        $novel = Genre::factory()->create(['name' => '小説']);
        Book::factory()->create(['title' => '坊っちゃん', 'author' => '夏目漱石'])->genres()->attach($novel);
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石'])->genres()->attach($novel);
        Book::factory()->create(['title' => '漱石論', 'author' => '夏目漱石']);

        $this->assertEqualsCanonicalizing(['坊っちゃん', '吾輩は猫である'], $this->titles("?genre={$novel->id}"));
        $this->assertSame(['坊っちゃん'], $this->titles("?genre={$novel->id}&keyword=坊"));
    }

    public function test_sort_orders(): void
    {
        $old = Book::factory()->create(['title' => 'B', 'created_at' => now()->subDays(2)]);
        $middle = Book::factory()->create(['title' => 'C', 'created_at' => now()->subDay()]);
        $new = Book::factory()->create(['title' => 'A', 'created_at' => now()]);
        Review::factory()->for($old)->create(['rating' => 5]);
        Review::factory()->for($new)->create(['rating' => 2]);

        $this->assertSame(['A', 'C', 'B'], $this->titles(''));
        $this->assertSame(['A', 'C', 'B'], $this->titles('?sort=latest'));
        $this->assertSame(['A', 'C', 'B'], $this->titles('?sort=newest'));
        $this->assertSame(['B', 'C', 'A'], $this->titles('?sort=oldest'));
        $this->assertSame(['A', 'B', 'C'], $this->titles('?sort=title'));
        // レビューがない C は最後
        $this->assertSame(['B', 'A', 'C'], $this->titles('?sort=rating'));
    }

    public function test_pagination_links_keep_search_conditions(): void
    {
        $genre = Genre::factory()->create();
        Book::factory()->count(11)->hasAttached($genre)->create(['title' => '検索対象']);

        $this->get("/books?keyword=検索&genre={$genre->id}&sort=oldest")
            ->assertSee('page=2', false)
            ->assertViewHas('books', fn ($books) => str_contains($books->url(2), 'keyword=')
                && str_contains($books->url(2), "genre={$genre->id}")
                && str_contains($books->url(2), 'sort=oldest'));
    }

    public function test_invalid_conditions_redirect_to_index_with_error(): void
    {
        $this->get('/books?sort=unknown')
            ->assertRedirect('/books')
            ->assertSessionHas('error', '並び順の指定が正しくありません。');
        $this->get('/books?genre=999')->assertSessionHas('error', '選択されたジャンルが存在しません。');
        $this->get('/books?keyword='.str_repeat('あ', 256))->assertSessionHas('error', 'キーワードは255文字以内で入力してください。');
    }

    public function test_index_shows_genre_options(): void
    {
        Genre::factory()->create(['name' => '旅行']);

        $this->get('/books')->assertSee('<option value="', false)->assertSee('旅行');
    }
}
