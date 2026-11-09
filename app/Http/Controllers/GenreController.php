<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('books')->orderBy('id')->get();

        return view('genres.index', compact('genres'));
    }

    public function show(Genre $genre)
    {
        $books = $genre->books()->with('genres')->latest('books.created_at')->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    public function create()
    {
        return view('genres.create');
    }

    public function store(GenreRequest $request)
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを登録しました');
    }

    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    public function update(GenreRequest $request, Genre $genre)
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを編集しました');
    }

    public function destroy(Genre $genre)
    {
        if (! $genre->deleteIfUnused()) {
            return redirect()->route('genres.index')->with('error', '書籍が登録されているジャンルは削除できません');
        }

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました');
    }
}
