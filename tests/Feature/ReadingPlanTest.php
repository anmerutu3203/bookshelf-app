<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
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
        $plan = ReadingPlan::factory()->create();

        $this->get('/reading-plans')->assertRedirect('/login');
        $this->get('/reading-plans/create')->assertRedirect('/login');
        $this->post('/reading-plans')->assertRedirect('/login');
        $this->get("/reading-plans/{$plan->id}/edit")->assertRedirect('/login');
        $this->post("/reading-plans/{$plan->id}/complete")->assertRedirect('/login');
    }

    public function test_index_shows_only_own_plans_and_filters_by_status(): void
    {
        $reading = ReadingPlan::factory()->for($this->user)->create();
        $done = ReadingPlan::factory()->for($this->user)->completed()->create();
        $others = ReadingPlan::factory()->create();

        $this->actingAs($this->user)->get('/reading-plans')
            ->assertOk()
            ->assertSee($reading->book->title)
            ->assertSee($done->book->title)
            ->assertDontSee($others->book->title);

        $this->actingAs($this->user)->get('/reading-plans?status=completed')
            ->assertViewHas('readingPlans', fn ($plans) => $plans->pluck('id')->all() === [$done->id])
            ->assertViewHas('currentStatus', 'completed');
        // 未知の値はすべて表示
        $this->actingAs($this->user)->get('/reading-plans?status=unknown')
            ->assertViewHas('readingPlans', fn ($plans) => $plans->count() === 2);
    }

    public function test_user_can_create_plan(): void
    {
        $book = Book::factory()->create(['title' => '計画する本']);

        $this->actingAs($this->user)->get('/reading-plans/create')->assertOk()->assertSee('計画する本');
        $this->actingAs($this->user)
            ->post('/reading-plans', ['book_id' => $book->id, 'target_date' => today()->addDays(10)->toDateString()])
            ->assertRedirect('/reading-plans')
            ->assertSessionHas('success', '読書計画を作成しました。');

        $plan = ReadingPlan::sole();
        $this->assertSame($this->user->id, $plan->user_id);
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);
        $this->assertTrue($plan->target_date->isSameDay(today()->addDays(10)));
    }

    public function test_create_validation(): void
    {
        $this->actingAs($this->user)->post('/reading-plans', [])
            ->assertSessionHasErrors(['book_id' => '書籍を選択してください。', 'target_date' => '期日を入力してください。']);
        $this->actingAs($this->user)->post('/reading-plans', ['book_id' => 999, 'target_date' => 'abc'])
            ->assertSessionHasErrors(['book_id' => '選択された書籍が存在しません。', 'target_date' => '期日は有効な日付で入力してください。']);
        $this->actingAs($this->user)->post('/reading-plans', ['book_id' => Book::factory()->create()->id, 'target_date' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors(['target_date' => '期日は今日以降の日付を入力してください。']);
        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_duplicate_in_progress_plan_for_same_book_is_rejected(): void
    {
        $book = Book::factory()->create();
        ReadingPlan::factory()->for($this->user)->for($book)->create();
        $data = ['book_id' => $book->id, 'target_date' => today()->toDateString()];

        $this->actingAs($this->user)->post('/reading-plans', $data)
            ->assertSessionHasErrors(['book_id' => 'この書籍には読書中の計画がすでにあります。']);

        // 読了済みなら同じ書籍でもう一度計画できる。他人の計画は関係ない
        ReadingPlan::query()->update(['status' => ReadingPlanStatus::Completed]);
        ReadingPlan::factory()->for($book)->create();
        $this->actingAs($this->user)->post('/reading-plans', $data)->assertSessionHasNoErrors();
    }

    public function test_owner_can_change_target_date_only(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->create();
        $otherBook = Book::factory()->create();

        $this->actingAs($this->user)->get("/reading-plans/{$plan->id}/edit")->assertOk()->assertSee($plan->book->title);
        $this->actingAs($this->user)
            ->put("/reading-plans/{$plan->id}", ['target_date' => today()->addMonth()->toDateString(), 'book_id' => $otherBook->id])
            ->assertRedirect('/reading-plans')
            ->assertSessionHas('success', '期日を変更しました。');

        $plan->refresh();
        $this->assertTrue($plan->target_date->isSameDay(today()->addMonth()));
        $this->assertNotSame($otherBook->id, $plan->book_id);
    }

    public function test_update_validation(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->create();

        $this->actingAs($this->user)->put("/reading-plans/{$plan->id}", ['target_date' => ''])
            ->assertSessionHasErrors(['target_date' => '期日を入力してください。']);
        $this->actingAs($this->user)->put("/reading-plans/{$plan->id}", ['target_date' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors(['target_date' => '期日は今日以降の日付を入力してください。']);
    }

    public function test_extending_expired_plan_returns_it_to_in_progress(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->expired()->create();

        $this->actingAs($this->user)->put("/reading-plans/{$plan->id}", ['target_date' => today()->toDateString()]);

        $this->assertSame(ReadingPlanStatus::InProgress, $plan->refresh()->status);
    }

    public function test_owner_can_complete_plan(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->create();

        $this->actingAs($this->user)->post("/reading-plans/{$plan->id}/complete")->assertRedirect('/reading-plans');

        $plan->refresh();
        $this->assertSame(ReadingPlanStatus::Completed, $plan->status);
        $this->assertNotNull($plan->completed_at);
    }

    public function test_completed_plan_cannot_be_edited_or_completed_again(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->completed()->create();

        $this->actingAs($this->user)->get("/reading-plans/{$plan->id}/edit")->assertForbidden();
        $this->actingAs($this->user)->put("/reading-plans/{$plan->id}", ['target_date' => today()->toDateString()])->assertForbidden();
        $this->actingAs($this->user)->post("/reading-plans/{$plan->id}/complete")->assertForbidden();
    }

    public function test_owner_can_delete_plan(): void
    {
        $plan = ReadingPlan::factory()->for($this->user)->completed()->create();

        $this->actingAs($this->user)->delete("/reading-plans/{$plan->id}")
            ->assertRedirect('/reading-plans')
            ->assertSessionHas('success', '読書計画を削除しました。');
        $this->assertModelMissing($plan);
    }

    public function test_other_users_plan_is_forbidden(): void
    {
        $plan = ReadingPlan::factory()->create();

        $this->actingAs($this->user)->get("/reading-plans/{$plan->id}/edit")->assertForbidden();
        $this->actingAs($this->user)->put("/reading-plans/{$plan->id}", ['target_date' => today()->toDateString()])->assertForbidden();
        $this->actingAs($this->user)->post("/reading-plans/{$plan->id}/complete")->assertForbidden();
        $this->actingAs($this->user)->delete("/reading-plans/{$plan->id}")->assertForbidden();
        $this->assertModelExists($plan);
    }

    public function test_seeded_plan_of_suzuki_is_forbidden_for_yamada(): void
    {
        $this->seed(DatabaseSeeder::class);

        // migrate:fresh 直後は id=6 になる。テストでは自動採番がリセットされないため id を引く
        $suzukiPlan = User::firstWhere('email', 'suzuki@example.com')->readingPlans()->sole();

        $this->actingAs(User::firstWhere('email', 'yamada@example.com'))
            ->get("/reading-plans/{$suzukiPlan->id}/edit")
            ->assertForbidden();
    }
}
