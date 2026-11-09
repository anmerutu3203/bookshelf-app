<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * 書籍を登録し、ジャンルを紐付ける。
     */
    public static function createWithGenres(User $user, array $attributes, array $genreIds): self
    {
        return DB::transaction(function () use ($user, $attributes, $genreIds) {
            $book = $user->books()->create($attributes);
            $book->genres()->sync($genreIds);

            return $book;
        });
    }

    /**
     * 書籍を更新し、ジャンルの紐付けを入れ替える。
     */
    public function updateWithGenres(array $attributes, array $genreIds): void
    {
        DB::transaction(function () use ($attributes, $genreIds) {
            $this->update($attributes);
            $this->genres()->sync($genreIds);
        });
    }
}
