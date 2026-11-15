<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * 自分のレビューにはいいねできない。
     */
    public function like(User $user, Review $review): bool
    {
        return $user->id !== $review->user_id;
    }
}
