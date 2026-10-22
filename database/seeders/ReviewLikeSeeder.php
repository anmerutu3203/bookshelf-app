<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        foreach (Review::all() as $review) {
            $otherUserIds = $userIds->reject(fn ($userId) => $userId === $review->user_id);

            $review->likedByUsers()->syncWithoutDetaching($otherUserIds->random(rand(0, 3)));
        }
    }
}
