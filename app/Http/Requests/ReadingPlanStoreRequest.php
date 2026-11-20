<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingPlanStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
                // 同じ書籍で読書中の計画が残っている間は、新しい計画を作れない
                Rule::unique('reading_plans', 'book_id')
                    ->where('user_id', $this->user()->id)
                    ->where('status', ReadingPlanStatus::InProgress->value),
            ],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.integer' => '選択された書籍が存在しません。',
            'book_id.exists' => '選択された書籍が存在しません。',
            'book_id.unique' => 'この書籍には読書中の計画がすでにあります。',
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は有効な日付で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を入力してください。',
        ];
    }
}
