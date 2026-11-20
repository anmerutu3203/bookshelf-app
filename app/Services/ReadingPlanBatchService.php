<?php

namespace App\Services;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReadingPlanBatchService
{
    /**
     * 日次バッチの本体。期限切れへの変更を先に行い、その後でリマインダーを送る。
     *
     * @return array{expired: int, notified: int}
     */
    public function run(?Carbon $today = null): array
    {
        $today ??= Carbon::today();

        // reading_plans の更新と notifications の作成をまとめて行う
        return DB::transaction(fn () => [
            'expired' => $this->expireOverduePlans($today),
            'notified' => $this->sendReminders($today),
        ]);
    }

    /**
     * 期日を過ぎた読書中の計画を期限切れにする。期日当日はまだ読書中のまま。
     */
    public function expireOverduePlans(Carbon $today): int
    {
        return ReadingPlan::where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', $today)
            ->update(['status' => ReadingPlanStatus::Expired]);
    }

    /**
     * 期日3日前・期日当日（読書中）と、期日から3日後（期限切れ）の計画にリマインダーを送る。
     * 同じ計画・同じタイミングの通知は一度だけ送る。
     */
    public function sendReminders(Carbon $today): int
    {
        $targets = collect([
            ReadingPlanReminder::THREE_DAYS_BEFORE => [ReadingPlanStatus::InProgress, $today->copy()->addDays(3)],
            ReadingPlanReminder::ON_DUE_DATE => [ReadingPlanStatus::InProgress, $today],
            ReadingPlanReminder::THREE_DAYS_AFTER => [ReadingPlanStatus::Expired, $today->copy()->subDays(3)],
        ]);

        return $targets
            ->flatMap(fn (array $condition, string $timing) => ReadingPlan::with(['user', 'book'])
                ->where('status', $condition[0])
                ->whereDate('target_date', $condition[1])
                ->get()
                ->reject(fn (ReadingPlan $plan) => $plan->user->notifications()
                    ->where('data->reading_plan_id', $plan->id)
                    ->where('data->timing', $timing)
                    ->exists())
                ->each(fn (ReadingPlan $plan) => $plan->user->notify(new ReadingPlanReminder($plan, $timing))))
            ->count();
    }
}
