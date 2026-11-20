<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'target_date' => today()->addWeek(),
            'status' => ReadingPlanStatus::InProgress,
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => ReadingPlanStatus::Completed, 'completed_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(['status' => ReadingPlanStatus::Expired, 'target_date' => today()->subDay()]);
    }
}
