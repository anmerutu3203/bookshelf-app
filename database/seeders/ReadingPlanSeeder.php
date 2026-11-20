<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $yamada = User::firstWhere('email', 'yamada@example.com');
        $suzuki = User::firstWhere('email', 'suzuki@example.com');
        // 計画ごとに別の書籍を使う
        $bookIds = Book::orderBy('id')->limit(6)->pluck('id');

        $plans = [
            // 3日前リマインダーの対象
            [$yamada, $today->copy()->addDays(3), ReadingPlanStatus::InProgress, null],
            // 当日リマインダーの対象
            [$yamada, $today->copy(), ReadingPlanStatus::InProgress, null],
            // 自動失効と、失効から3日後の再エンゲージメントの対象
            [$yamada, $today->copy()->subDays(3), ReadingPlanStatus::InProgress, null],
            // 通知の対象外
            [$yamada, $today->copy()->addDays(7), ReadingPlanStatus::InProgress, null],
            [$yamada, $today->copy()->subDays(10), ReadingPlanStatus::Completed, $today->copy()->subDays(5)],
            // 山田でログインして /reading-plans/6/edit を開くと 403 になることの確認用
            [$suzuki, $today->copy()->addDays(5), ReadingPlanStatus::InProgress, null],
        ];

        foreach ($plans as $index => [$user, $targetDate, $status, $completedAt]) {
            ReadingPlan::create([
                'user_id' => $user->id,
                'book_id' => $bookIds[$index],
                'target_date' => $targetDate,
                'status' => $status,
                'completed_at' => $completedAt,
            ]);
        }
    }
}
