<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        // ponytail: 画面にページネーションがないため全件表示。件数が増えたら paginate と Blade の links() を追加する
        $notifications = $request->user()->notifications()->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 自分宛ての通知だけを探すため、他人の通知 ID は 404 になる。
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return redirect()->route('notifications.index')->with('success', '通知を既読にしました。');
    }
}
