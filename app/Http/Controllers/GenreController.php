<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧を書籍数付きで表示する。
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->orderBy('id')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンルに属する書籍を10件ずつ新しい順に表示する。
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()->with('genres')->latest('books.created_at')->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル登録フォームを表示する。
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する。
     */
    public function store(GenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを作成しました。');
    }

    /**
     * ジャンル編集フォームを表示する。
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンルを更新する。
     */
    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました');
    }

    /**
     * 書籍が紐付いていなければジャンルを削除する。
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if (! $genre->deleteIfUnused()) {
            return redirect()->route('genres.index')->with('error', 'このジャンルには書籍が紐づいているため削除できません');
        }

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました');
    }
}
