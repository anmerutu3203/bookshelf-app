<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanStoreRequest;
use App\Http\Requests\ReadingPlanUpdateRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    public function index(Request $request): View
    {
        // 未知の status はすべて表示として扱う
        $status = ReadingPlanStatus::tryFrom((string) $request->query('status'));

        $readingPlans = $request->user()->readingPlans()
            ->ofStatus($status)
            ->with('book')
            ->orderBy('target_date')
            ->orderBy('id')
            ->get();
        $currentStatus = $status?->value;

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    public function store(ReadingPlanStoreRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create($request->validated());

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $readingPlan = $plan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(ReadingPlanUpdateRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $plan->changeTargetDate($request->date('target_date'));

        return redirect()->route('reading-plans.index')->with('success', '期日を変更しました。');
    }

    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->complete();

        return redirect()->route('reading-plans.index')->with('success', '読了おめでとうございます！読書計画を読了にしました。');
    }

    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }
}
