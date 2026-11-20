<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * 期日の変更は所有者のみ。読了済みの計画は変更できない。
     */
    public function update(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id && $plan->status !== ReadingPlanStatus::Completed;
    }

    /**
     * 読了にできるのは所有者のみ。読了済みの計画は対象外。
     */
    public function complete(User $user, ReadingPlan $plan): bool
    {
        return $this->update($user, $plan);
    }

    public function delete(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }
}
