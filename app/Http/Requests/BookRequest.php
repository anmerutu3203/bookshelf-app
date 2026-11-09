<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            // 登録時は route('book') が null になるため、除外なしの一意チェックになる
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です',
            'title.string' => 'タイトルは文字列で入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.required' => '著者は必須です',
            'author.string' => '著者は文字列で入力してください',
            'author.max' => '著者は255文字以内で入力してください',
            'isbn.required' => 'ISBNは必須です',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください',
            'isbn.unique' => 'このISBNは既に登録されています',
            'published_date.required' => '出版日は必須です',
            'published_date.date' => '出版日は有効な日付で入力してください',
            'description.string' => '説明は文字列で入力してください',
            'image_url.url' => '画像URLはURL形式で入力してください',
            'image_url.max' => '画像URLは255文字以内で入力してください',
            'genres.required' => 'ジャンルは1つ以上選択してください',
            'genres.array' => 'ジャンルは1つ以上選択してください',
            'genres.min' => 'ジャンルは1つ以上選択してください',
            'genres.*.integer' => '選択されたジャンルが存在しません',
            'genres.*.exists' => '選択されたジャンルが存在しません',
        ];
    }
}
