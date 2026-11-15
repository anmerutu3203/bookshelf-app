<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // 投稿

    public function test_user_can_post_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->post("/books/{$book->id}/reviews", ['rating' => 4, 'comment' => '読みやすい'])
            ->assertRedirect("/books/{$book->id}")
            ->assertSessionHas('success', 'レビューを投稿しました');

        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 4, 'comment' => '読みやすい']);
    }

    public function test_guest_cannot_post_review(): void
    {
        $book = Book::factory()->create();

        $this->post("/books/{$book->id}/reviews", ['rating' => 4, 'comment' => '読みやすい'])->assertRedirect('/login');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_post_validates_rating_and_comment(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->post("/books/{$book->id}/reviews", [])
            ->assertSessionHasErrors(['rating' => '評価は必須です', 'comment' => 'コメントは必須です']);

        $this->actingAs($user)->post("/books/{$book->id}/reviews", ['rating' => 'abc', 'comment' => '感想'])
            ->assertSessionHasErrors(['rating' => '評価は整数で入力してください']);
        foreach ([0, 6] as $rating) {
            $this->actingAs($user)->post("/books/{$book->id}/reviews", ['rating' => $rating, 'comment' => '感想'])
                ->assertSessionHasErrors(['rating' => '評価は１～５の整数で入力してください']);
        }
        $this->actingAs($user)->post("/books/{$book->id}/reviews", ['rating' => 3, 'comment' => ['配列']])
            ->assertSessionHasErrors(['comment' => 'コメントは文字列で入力してください']);

        $this->actingAs($user)->post("/books/{$book->id}/reviews", ['rating' => 3, 'comment' => str_repeat('あ', 1001)])
            ->assertSessionHasErrors(['comment' => 'コメントは1000文字以内で入力してください']);
        $this->actingAs($user)->post("/books/{$book->id}/reviews", ['rating' => 3, 'comment' => str_repeat('あ', 1000)])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_user_can_post_second_review_for_same_book(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($review->user)->post("/books/{$review->book_id}/reviews", ['rating' => 5, 'comment' => '2回目'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reviews', 2);
    }

    // 編集

    public function test_owner_can_view_edit_page(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($review->user)->get("/reviews/{$review->id}/edit")
            ->assertOk()
            ->assertViewIs('reviews.edit')
            ->assertSee($review->book->title);
    }

    public function test_owner_can_update_review(): void
    {
        $review = Review::factory()->create(['rating' => 2]);

        $this->actingAs($review->user)->put("/reviews/{$review->id}", ['rating' => 5, 'comment' => '読み直して評価が変わった'])
            ->assertRedirect("/books/{$review->book_id}")
            ->assertSessionHas('success', 'レビューを更新しました');

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5, 'comment' => '読み直して評価が変わった']);
    }

    public function test_update_validates_rating(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($review->user)->put("/reviews/{$review->id}", ['rating' => 6, 'comment' => '感想'])
            ->assertSessionHasErrors(['rating' => '評価は１～５の整数で入力してください']);
    }

    public function test_other_user_cannot_edit_update_or_delete_review(): void
    {
        $review = Review::factory()->create(['rating' => 3]);
        $other = User::factory()->create();

        $this->actingAs($other)->get("/reviews/{$review->id}/edit")->assertForbidden();
        $this->actingAs($other)->put("/reviews/{$review->id}", ['rating' => 1, 'comment' => '改ざん'])->assertForbidden();
        $this->actingAs($other)->delete("/reviews/{$review->id}")->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3]);
    }

    public function test_guest_is_redirected_to_login_from_edit(): void
    {
        $review = Review::factory()->create();

        $this->get("/reviews/{$review->id}/edit")->assertRedirect('/login');
    }

    // 削除

    public function test_owner_can_delete_review_with_likes(): void
    {
        $review = Review::factory()->create();
        $review->likedByUsers()->attach(User::factory()->create());

        $this->actingAs($review->user)->delete("/reviews/{$review->id}")
            ->assertRedirect("/books/{$review->book_id}")
            ->assertSessionHas('success', 'レビューを削除しました');

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
    }

    public function test_edit_and_delete_links_are_shown_only_to_owner(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($review->user)->get("/books/{$review->book_id}")->assertSee("/reviews/{$review->id}/edit");
        $this->actingAs(User::factory()->create())->get("/books/{$review->book_id}")->assertDontSee("/reviews/{$review->id}/edit");
    }
}
