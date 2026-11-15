<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_toggle_adds_and_removes_like(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();
        $bookPage = "/books/{$review->book_id}";

        $this->actingAs($user)->from($bookPage)->post("/reviews/{$review->id}/like")
            ->assertRedirect($bookPage)
            ->assertSessionMissing('success');
        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
        $this->get($bookPage)->assertSee('いいね済み (1)');

        $this->actingAs($user)->from($bookPage)->post("/reviews/{$review->id}/like")
            ->assertRedirect($bookPage);
        $this->assertDatabaseMissing('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
        $this->actingAs($user->fresh())->get($bookPage)->assertSee('いいね (0)');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $review = Review::factory()->create();

        $this->post("/reviews/{$review->id}/like")->assertRedirect('/login');
        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_user_cannot_like_own_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($review->user)->post("/reviews/{$review->id}/like")->assertForbidden();
        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_toggle_returns_404_for_missing_review(): void
    {
        $this->actingAs(User::factory()->create())->post('/reviews/999/like')->assertNotFound();
    }
}
