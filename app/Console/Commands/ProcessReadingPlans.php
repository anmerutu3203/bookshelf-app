<?php

namespace App\Console\Commands;

use App\Services\ReadingPlanBatchService;
use Illuminate\Console\Command;

class ProcessReadingPlans extends Command
{
    protected $signature = 'reading-plans:process';

    protected $description = '期日を過ぎた読書計画を期限切れにし、リマインダー通知を送る';

    /**
     * 期限切れ計画の自動失効とリマインダー通知を実行する。
     */
    public function handle(ReadingPlanBatchService $batch): int
    {
        $result = $batch->run();

        $this->info("期限切れにした計画: {$result['expired']}件 / 送った通知: {$result['notified']}件");

        return self::SUCCESS;
    }
}
