<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $bookIds = Book::pluck('id');

        foreach (User::all() as $user) {
            $user->favoriteBooks()->syncWithoutDetaching($bookIds->random(rand(3, 5)));
        }
    }
}
