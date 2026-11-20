<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReadingPlanBatchTest extends TestCase
{
    use RefreshDatabase;

    private function plan(int $days, ReadingPlanStatus $status = ReadingPlanStatus::InProgress): ReadingPlan
    {
        return ReadingPlan::factory()->create(['target_date' => today()->addDays($days), 'status' => $status]);
    }

    public function test_overdue_in_progress_plans_expire(): void
    {
        $overdue = $this->plan(-1);
        $today = $this->plan(0);
        $completed = $this->plan(-5, ReadingPlanStatus::Completed);

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $overdue->refresh()->status);
        $this->assertSame(ReadingPlanStatus::InProgress, $today->refresh()->status);
        $this->assertSame(ReadingPlanStatus::Completed, $completed->refresh()->status);
    }

    public function test_reminders_are_sent_by_timing(): void
    {
        Notification::fake();
        $threeDaysBefore = $this->plan(3);
        $dueToday = $this->plan(0);
        $threeDaysAfter = $this->plan(-3);
        $this->plan(7);
        $this->plan(2);
        $this->plan(-4);
        $this->plan(3, ReadingPlanStatus::Completed);

        $this->artisan('reading-plans:process')
            ->expectsOutput('期限切れにした計画: 2件 / 送った通知: 3件')
            ->assertSuccessful();

        Notification::assertCount(3);
        foreach ([
            ReadingPlanReminder::THREE_DAYS_BEFORE => $threeDaysBefore,
            ReadingPlanReminder::ON_DUE_DATE => $dueToday,
            ReadingPlanReminder::THREE_DAYS_AFTER => $threeDaysAfter,
        ] as $timing => $plan) {
            Notification::assertSentTo($plan->user, ReadingPlanReminder::class,
                fn (ReadingPlanReminder $notification, array $channels, User $user) => $channels === ['database']
                    && $notification->toArray($user)['timing'] === $timing
                    && $notification->toArray($user)['reading_plan_id'] === $plan->id);
        }
    }

    public function test_notification_is_saved_to_database_only_once(): void
    {
        $plan = $this->plan(0);
        $plan->book->update(['title' => '火花']);

        $this->artisan('reading-plans:process');
        $this->artisan('reading-plans:process');

        $this->assertSame(1, $plan->user->notifications()->count());
        $data = $plan->user->notifications()->first()->data;
        $this->assertSame('on_due_date', $data['timing']);
        $this->assertSame('今日が読書計画の期日です', $data['title']);
        $this->assertStringContainsString('『火花』', $data['body']);
    }

    public function test_seeded_scenarios(): void
    {
        $this->seed(DatabaseSeeder::class);
        $yamada = User::firstWhere('email', 'yamada@example.com');

        $this->artisan('reading-plans:process')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['three_days_before', 'on_due_date', 'three_days_after'],
            $yamada->notifications->pluck('data.timing')->all(),
        );
        $this->assertSame(1, $yamada->readingPlans()->where('status', ReadingPlanStatus::Expired)->count());
        $this->assertSame(0, User::firstWhere('email', 'suzuki@example.com')->notifications()->count());
    }

    public function test_command_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'reading-plans:process'));

        $this->assertNotNull($event);
        $this->assertSame('0 9 * * *', $event->expression);
    }
}
