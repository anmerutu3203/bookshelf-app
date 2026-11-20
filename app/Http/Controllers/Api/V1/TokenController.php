<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * メールアドレスとパスワードを確認し、Bearer トークンを発行する。
     */
    public function store(TokenRequest $request): JsonResponse
    {
        $user = User::firstWhere('email', $request->validated('email'));

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
        }

        $token = $user->createToken($request->validated('device_name') ?? 'api')->plainTextToken;

        return response()->json(['token' => $token, 'token_type' => 'Bearer'], 201);
    }

    /**
     * リクエストに使ったトークンを無効にする。
     */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
