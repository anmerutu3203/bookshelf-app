<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    /**
     * ISBN で Google Books API を検索し、書籍フォームの項目に合わせた形で返す。見つからなければ null。
     *
     * @return array{title: ?string, author: ?string, published_date: ?string, description: ?string, image_url: ?string}|null
     *
     * @throws ConnectionException 接続できない・タイムアウトしたとき
     * @throws RequestException API がエラーを返したとき
     */
    public function findByIsbn(string $isbn): ?array
    {
        $volume = Http::timeout(5)
            ->get(config('services.google_books.url'), array_filter([
                'q' => "isbn:{$isbn}",
                'key' => config('services.google_books.key'),
            ]))
            ->throw()
            ->json('items.0.volumeInfo');

        if ($volume === null) {
            return null;
        }

        return [
            'title' => $volume['title'] ?? null,
            'author' => isset($volume['authors']) ? implode('、', $volume['authors']) : null,
            // 「2012」「2012-06」のように年月までしかない場合もある
            'published_date' => $volume['publishedDate'] ?? null,
            'description' => $volume['description'] ?? null,
            // 画像 URL は http で返るため https に揃える
            'image_url' => isset($volume['imageLinks']['thumbnail'])
                ? preg_replace('/^http:/', 'https:', $volume['imageLinks']['thumbnail'])
                : null,
        ];
    }
}
