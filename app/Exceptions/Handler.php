<?php

namespace App\Exceptions;

use App\Models\Book;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 存在しない ID などの 404 を、API ではモデル名を出さずに日本語で返す
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = $e->getPrevious() instanceof ModelNotFoundException && $e->getPrevious()->getModel() === Book::class
                ? '書籍が見つかりませんでした。'
                : '指定されたデータが見つかりません。';

            return response()->json(['error' => $message], 404);
        });

        // API の未認証（トークンなし・無効）と認可エラーも、404 と同じ error キーで日本語にする
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => '認証が必要です。'], 401);
            }
        });
        $this->renderable(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'この操作を行う権限がありません。'], 403);
            }
        });
    }

    /**
     * API は Accept ヘッダーがなくても、エラーを JSON で返す。
     */
    protected function shouldReturnJson($request, Throwable $e)
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }
}
