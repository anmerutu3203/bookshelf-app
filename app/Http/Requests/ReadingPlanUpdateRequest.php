<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 編集で変更できるのは期日だけ。
 */
class ReadingPlanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は有効な日付で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を入力してください。',
        ];
    }
}
