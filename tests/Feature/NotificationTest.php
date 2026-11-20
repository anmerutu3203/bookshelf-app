<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function notifyUser(User $user): string
    {
        $plan = ReadingPlan::factory()->for($user)->create(['target_date' => today()]);
        $user->notify(new ReadingPlanReminder($plan, ReadingPlanReminder::ON_DUE_DATE));

        return $user->notifications()->latest()->first()->id;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
        $this->post('/notifications/abc/read')->assertRedirect('/login');
    }

    public function test_index_shows_own_notifications_and_unread_badge(): void
    {
        $user = User::factory()->create();
        $this->notifyUser($user);
        $this->notifyUser(User::factory()->create());

        $this->actingAs($user)->get('/notifications')
            ->assertOk()
            ->assertViewHas('notifications', fn ($notifications) => $notifications->count() === 1)
            ->assertSee('今日が読書計画の期日です')
            ->assertSee('未読')
            ->assertSee('既読にする');
    }

    public function test_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get('/notifications')->assertSee('通知はありません。');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $id = $this->notifyUser($user);

        $this->actingAs($user)->post("/notifications/{$id}/read")
            ->assertRedirect('/notifications')
            ->assertSessionHas('success', '通知を既読にしました。');

        $this->assertNotNull($user->notifications()->find($id)->read_at);
        $this->actingAs($user)->get('/notifications')->assertDontSee('既読にする');
    }

    public function test_other_users_notification_cannot_be_read(): void
    {
        $id = $this->notifyUser(User::factory()->create());

        $this->actingAs(User::factory()->create())->post("/notifications/{$id}/read")->assertNotFound();
        $this->assertDatabaseHas('notifications', ['id' => $id, 'read_at' => null]);
    }
}
