<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\BookRequest as WebBookRequest;

/**
 * Web 版と同じルールに、登録者 ID のチェックを加える。
 */
class BookRequest extends WebBookRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'user_id.required' => '登録者IDは必須です',
            'user_id.integer' => '登録者IDは整数で入力してください',
            'user_id.exists' => '指定された登録者が存在しません',
        ];
    }
}
