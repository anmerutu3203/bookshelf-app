<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * 書籍の登録者。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 書籍に紐付くジャンル。
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    /**
     * 書籍へのレビュー。
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * 書籍をお気に入りに追加したユーザー。
     */
    public function favoritedByUsers(): BelongsToMany
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
     * 並び順を指定する。rating は平均評価の高い順で、レビューがない書籍（平均が NULL）は最後になる。
     * 同じ順位の書籍は新しい順に並べる。rating を使うときは withAvg('reviews', 'rating') を併用すること。
     */
    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->oldest()->orderBy('id'),
            'title' => $query->orderBy('title')->orderByDesc('id'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->latest()->orderByDesc('id'),
            default => $query->latest()->orderByDesc('id'),
        };
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
