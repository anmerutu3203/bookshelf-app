<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 登録時は route('genre') が null になるため、除外なしの一意チェックになる
            'name' => ['required', 'string', 'max:255', Rule::unique('genres', 'name')->ignore($this->route('genre'))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'ジャンル名は必須です',
            'name.string' => 'ジャンル名は文字列で入力してください',
            'name.max' => 'ジャンル名は255文字以内で入力してください',
            'name.unique' => 'このジャンル名は既に登録されています',
        ];
    }
}
