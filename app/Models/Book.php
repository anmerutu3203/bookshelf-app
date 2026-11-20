<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * タイトルか著者の部分一致と、ジャンルで絞り込む。未指定の条件は無視する。
     */
    public function scopeSearch(Builder $query, ?string $keyword, ?int $genreId): Builder
    {
        return $query
            ->when($keyword, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$keyword}%")
                ->orWhere('author', 'like', "%{$keyword}%")))
            ->when($genreId, fn (Builder $query) => $query->whereHas('genres', fn (Builder $query) => $query->whereKey($genreId)));
    }
}
