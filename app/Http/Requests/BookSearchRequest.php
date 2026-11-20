<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BookSearchRequest extends FormRequest
{
    // Blade の並び順プルダウンは「新しい順」を newest で送るため、latest と同じ扱いにする
    public const SORTS = ['latest', 'newest', 'oldest', 'title', 'rating'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'integer', 'exists:genres,id'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'genre.integer' => '選択されたジャンルが存在しません。',
            'genre.exists' => '選択されたジャンルが存在しません。',
            'sort.in' => '並び順の指定が正しくありません。',
        ];
    }

    /**
     * 一覧画面にはフォームのエラー表示欄がないため、条件をリセットした一覧に戻してエラーを表示する。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route('books.index')->with('error', $validator->errors()->first())
        );
    }
}
