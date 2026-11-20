<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    protected $casts = [
        'published_date' => 'date',
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
     * 平均評価の高い順に上位の書籍を返す。レビューがない書籍は含めない。
     * 平均評価が同じならレビュー件数が多い順、さらに同じなら登録が古い順。
     */
    public static function topRated(int $limit = 10): Collection
    {
        return static::has('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderBy('id')
            ->limit($limit)
            ->get();
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
