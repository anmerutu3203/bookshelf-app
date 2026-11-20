<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'status' => ReadingPlanStatus::class,
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'in_progress',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * 状態で絞り込む。null ならすべて。
     */
    public function scopeOfStatus(Builder $query, ?ReadingPlanStatus $status): Builder
    {
        return $query->when($status, fn (Builder $query) => $query->where('status', $status));
    }

    /**
     * 読了にする。
     */
    public function complete(): void
    {
        $this->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * 期日を変更する。期限切れの計画は、今日以降の期日にすると読書中に戻る。
     */
    public function changeTargetDate(Carbon $targetDate): void
    {
        $this->target_date = $targetDate;

        if ($this->status === ReadingPlanStatus::Expired && $targetDate->gte(today())) {
            $this->status = ReadingPlanStatus::InProgress;
        }

        $this->save();
    }
}
