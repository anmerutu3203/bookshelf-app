<?php

namespace App\Http\Controllers;

use App\Services\ReadingReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する。
     */
    public function index(Request $request, ReadingReportService $reports): View
    {
        $stats = $reports->build($request->user());

        return view('reports.index', compact('stats'));
    }
}
