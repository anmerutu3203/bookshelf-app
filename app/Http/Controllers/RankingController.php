<?php

namespace App\Http\Controllers;

use App\Models\Book;

class RankingController extends Controller
{
    public function index()
    {
        $rankedBooks = Book::topRated();

        return view('ranking.index', compact('rankedBooks'));
    }
}
