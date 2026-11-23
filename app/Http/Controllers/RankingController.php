<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * 平均評価 TOP10 のランキングを表示する。
     */
    public function index(): View
    {
        $rankedBooks = Book::topRated();

        return view('ranking.index', compact('rankedBooks'));
    }
}
