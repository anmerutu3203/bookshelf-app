<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function books()
    {
        return $this->belongsToMany(Book::class)->withTimestamps();
    }

    /**
     * 書籍が紐付いていなければ削除する。紐付いていれば削除せず false を返す。
     */
    public function deleteIfUnused(): bool
    {
        // 判定後に紐付けが増えても、外部キーの restrictOnDelete で削除は失敗する
        if ($this->books()->exists()) {
            return false;
        }

        return $this->delete();
    }
}
