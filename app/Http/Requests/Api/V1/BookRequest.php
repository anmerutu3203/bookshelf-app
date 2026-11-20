<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\BookRequest as WebBookRequest;
use Illuminate\Validation\Rule;

/**
 * Web 版と同じルールに、登録者 ID のチェックを加える。
 */
class BookRequest extends WebBookRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            // Web 版では任意だが、API 版は必須のまま維持する（PM 指示）
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'published_date' => ['required', 'date'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'isbn.required' => 'ISBNは必須です。',
            'published_date.required' => '出版日は必須です。',
            'user_id.required' => '登録者IDは必須です。',
            'user_id.integer' => '登録者IDは整数で入力してください。',
            'user_id.exists' => '指定された登録者は存在しません。',
        ];
    }
}
