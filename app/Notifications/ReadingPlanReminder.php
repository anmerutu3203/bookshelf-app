<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    // 通知一覧の Blade が色とアイコンを切り替えるキー
    public const THREE_DAYS_BEFORE = 'three_days_before';

    public const ON_DUE_DATE = 'on_due_date';

    public const THREE_DAYS_AFTER = 'three_days_after';

    public function __construct(private ReadingPlan $plan, private string $timing) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->plan->book->title;
        $date = $this->plan->target_date->format('Y-m-d');

        [$heading, $body] = match ($this->timing) {
            self::THREE_DAYS_BEFORE => ['読書計画の期日まであと3日です', "『{$title}』の期日は {$date} です。計画どおりに読み進めましょう。"],
            self::ON_DUE_DATE => ['今日が読書計画の期日です', "『{$title}』の期日は今日です。読み終えたら「読了する」を押しましょう。"],
            self::THREE_DAYS_AFTER => ['読書計画をもう一度始めませんか？', "『{$title}』の計画は期限切れになっています。期日を設定し直して、読書を再開しましょう。"],
        };

        return [
            'reading_plan_id' => $this->plan->id,
            'book_id' => $this->plan->book_id,
            'timing' => $this->timing,
            'title' => $heading,
            'body' => $body,
        ];
    }
}
