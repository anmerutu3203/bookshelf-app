<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IsbnSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * URL の {isbn} をバリデーションの対象にする。
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['isbn' => $this->route('isbn')]);
    }

    public function rules(): array
    {
        return [
            'isbn' => ['required', 'digits:13'],
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください。',
        ];
    }

    /**
     * 画面の JavaScript は error キーを表示するため、その形で返す。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['error' => $validator->errors()->first()], 422));
    }
}
