<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            // レビューがない書籍は null
            'average_rating' => $this->whenHas('reviews_avg_rating', fn () => $this->reviews_avg_rating === null ? null : round($this->reviews_avg_rating, 2)),
            'review_count' => $this->whenCounted('reviews'),
            // ponytail: 詳細の項目は PM に確認中のため、一覧以外では従来の項目を残している
            $this->mergeUnless($request->routeIs('api.v1.books.index'), fn () => [
                'description' => $this->description,
                'image_url' => $this->image_url,
                'user_id' => $this->user_id,
                'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]),
        ];
    }
}
