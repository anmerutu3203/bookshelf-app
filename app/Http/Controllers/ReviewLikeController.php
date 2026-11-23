<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    /**
     * レビューへのいいねを追加・解除する。自分のレビューにもいいねできる。
     * 戻った画面がページ先頭にスクロールしないよう、押したレビューの位置（#review-{id}）に戻す。
     */
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        $request->user()->likedReviews()->toggle($review->id);

        return redirect()->to(url()->previous()."#review-{$review->id}");
    }
}
