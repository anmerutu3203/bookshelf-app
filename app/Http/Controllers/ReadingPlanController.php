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
    /**
     * 自分の読書計画を期日順に表示する。?status= で状態を絞り込む。
     */
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

    /**
     * 読書計画の作成フォームを表示する。
     */
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を作成する。
     */
    public function store(ReadingPlanStoreRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create($request->validated());

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書計画の期日変更フォームを表示する（所有者のみ）。
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        $readingPlan = $plan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の期日を変更する（所有者のみ）。
     */
    public function update(ReadingPlanUpdateRequest $request, ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $plan->changeTargetDate($request->date('target_date'));

        return redirect()->route('reading-plans.index')->with('success', '期日を変更しました。');
    }

    /**
     * 読書計画を読了にする（所有者のみ）。
     */
    public function complete(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('complete', $plan);

        $plan->complete();

        return redirect()->route('reading-plans.index')->with('success', '読了おめでとうございます！読書計画を読了にしました。');
    }

    /**
     * 読書計画を削除する（所有者のみ）。
     */
    public function destroy(ReadingPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }
}
