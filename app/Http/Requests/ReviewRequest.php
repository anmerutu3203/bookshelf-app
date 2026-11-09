<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価は必須です',
            'rating.integer' => '評価は1〜5で選択してください',
            'rating.between' => '評価は1〜5で選択してください',
            'comment.required' => 'コメントは必須です',
            'comment.string' => 'コメントは文字列で入力してください',
        ];
    }

    /**
     * 投稿時のみ、同じ書籍への2件目のレビューを拒否する（DB の unique 制約と同じ条件）。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $book = $this->route('book');

            if ($book && $this->user()->reviews()->where('book_id', $book->id)->exists()) {
                $validator->errors()->add('rating', 'この書籍には既にレビューを投稿しています');
            }
        });
    }
}
