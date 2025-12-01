<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneConsumableLogs extends Command
{
    protected $signature = 'consumables:prune-logs {--days=90}';

    protected $description = 'Delete consumable logs older than N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        \App\Models\ConsumableLog::where('created_at', '<', now()->subDays($days))->delete();
        $this->info('Pruned logs older than '.$days.' days');

        return self::SUCCESS;
    }
}
