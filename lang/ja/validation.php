<?php

// 各 FormRequest の messages() で定義していないルールの受け皿
return [
    'required' => ':attributeを入力してください',
    'string' => ':attributeは文字列で入力してください',
    'email' => ':attributeはメール形式で入力してください',
    'unique' => 'この:attributeは既に登録されています',
    'confirmed' => ':attributeと確認用の値が一致しません',
    'integer' => ':attributeは整数で入力してください',
    'date' => ':attributeは有効な日付で入力してください',
    'url' => ':attributeはURL形式で入力してください',
    'array' => ':attributeは配列で指定してください',
    'exists' => '選択された:attributeは存在しません',
    'between' => [
        'numeric' => ':attributeは:minから:maxの間で指定してください',
    ],
    'max' => [
        'numeric' => ':attributeは:max以下で指定してください',
        'string' => ':attributeは:max文字以内で入力してください',
    ],
    'min' => [
        'numeric' => ':attributeは:min以上で指定してください',
        'string' => ':attributeは:min文字以上で入力してください',
        'array' => ':attributeは:min個以上選択してください',
    ],

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
    ],
];
