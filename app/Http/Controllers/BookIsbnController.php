<?php

namespace App\Http\Controllers;

use App\Http\Requests\IsbnSearchRequest;
use App\Services\GoogleBooksService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class BookIsbnController extends Controller
{
    public function __invoke(IsbnSearchRequest $request, GoogleBooksService $googleBooks): JsonResponse
    {
        try {
            $book = $googleBooks->findByIsbn($request->validated('isbn'));
        } catch (ConnectionException|RequestException $e) {
            report($e);

            return response()->json(['error' => '書籍情報を取得できませんでした。時間をおいて再度お試しください。'], 502);
        }

        if ($book === null) {
            return response()->json(['error' => '該当する書籍が見つかりませんでした。'], 404);
        }

        return response()->json($book);
    }
}
