<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://www.googleapis.com/books/v1/volumes*';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
    }

    public function test_returns_book_info_for_form(): void
    {
        config(['services.google_books.key' => 'test-key']);
        Http::fake([self::URL => Http::response(['totalItems' => 1, 'items' => [['volumeInfo' => [
            'title' => 'リーダブルコード',
            'authors' => ['Dustin Boswell', 'Trevor Foucher'],
            'publishedDate' => '2012-06-23',
            'description' => '読みやすいコードの本',
            'imageLinks' => ['thumbnail' => 'http://books.google.com/books/content?id=xxx'],
        ]]]])]);

        $this->getJson('/books/isbn/9784873115658')
            ->assertOk()
            ->assertExactJson([
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell、Trevor Foucher',
                'published_date' => '2012-06-23',
                'description' => '読みやすいコードの本',
                'image_url' => 'https://books.google.com/books/content?id=xxx',
            ]);

        Http::assertSent(fn (Request $request) => $request['q'] === 'isbn:9784873115658' && $request['key'] === 'test-key');
    }

    public function test_missing_fields_are_null(): void
    {
        Http::fake([self::URL => Http::response(['items' => [['volumeInfo' => ['title' => 'タイトルのみ']]]])]);

        $this->getJson('/books/isbn/9784873115658')
            ->assertOk()
            ->assertJson(['title' => 'タイトルのみ', 'author' => null, 'published_date' => null, 'description' => null, 'image_url' => null]);
    }

    public function test_returns_404_when_not_found(): void
    {
        Http::fake([self::URL => Http::response(['totalItems' => 0])]);

        $this->getJson('/books/isbn/9780000000000')
            ->assertNotFound()
            ->assertExactJson(['error' => '該当する書籍が見つかりませんでした。']);
    }

    public function test_returns_502_when_api_fails(): void
    {
        Http::fake([self::URL => Http::response([], 503)]);
        $this->getJson('/books/isbn/9784873115658')
            ->assertStatus(502)
            ->assertExactJson(['error' => '書籍情報を取得できませんでした。時間をおいて再度お試しください。']);
    }

    public function test_returns_502_when_connection_fails(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->getJson('/books/isbn/9784873115658')->assertStatus(502);
    }

    public function test_rejects_invalid_isbn_without_calling_api(): void
    {
        Http::fake();

        $this->getJson('/books/isbn/123')
            ->assertUnprocessable()
            ->assertExactJson(['error' => 'ISBNは13桁の数字で入力してください。']);
        $this->getJson('/books/isbn/978487311565x')->assertUnprocessable();

        Http::assertNothingSent();
    }

    public function test_guest_cannot_search(): void
    {
        auth()->logout();

        $this->get('/books/isbn/9784873115658')->assertRedirect('/login');
    }
}
