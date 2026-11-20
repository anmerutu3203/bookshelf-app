<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\BookRequest as WebBookRequest;
use Illuminate\Validation\Rule;

/**
 * Web 版と同じルールを使い、ISBN と出版日だけは API 版では必須のまま維持する（PM 指示）。
 * 登録者はトークンの持ち主にするため、user_id は受け取らない。
 */
class BookRequest extends WebBookRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'published_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'isbn.required' => 'ISBNは必須です。',
            'published_date.required' => '出版日は必須です。',
        ];
    }
}
