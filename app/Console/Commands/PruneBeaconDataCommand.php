<?php

namespace App\Console\Commands;

use App\Models\NewsItem;
use App\Models\SourceFetchLog;
use Illuminate\Console\Command;

/**
 * Purga automática de registros antiguos según la política configurada en config/beacon.php:
 * - source_fetch_logs: 30 días por defecto (configurable).
 * - news_items: 0 (sin borrado por defecto, configurable).
 */
class PruneBeaconDataCommand extends Command
{
    protected $signature = 'beacon:prune';

    protected $description = 'Elimina logs de ingesta expirados (30 días por defecto) y noticias si la retención está activada';

    public function handle(): int
    {
        $logDays = (int) config('beacon.fetch_logs_retention_days', 30);
        $deletedLogs = 0;

        if ($logDays > 0) {
            $deletedLogs = SourceFetchLog::query()
                ->where('fetched_at', '<', now()->subDays($logDays))
                ->delete();
        }

        $itemDays = (int) config('beacon.items_retention_days', 0);
        $deletedItems = 0;

        if ($itemDays > 0) {
            $deletedItems = NewsItem::query()
                ->where('published_at', '<', now()->subDays($itemDays))
                ->delete();
        }

        $this->info(sprintf(
            'Purga completada: %d logs eliminados (retención %d días), %d noticias eliminadas (retención %d días).',
            $deletedLogs,
            $logDays,
            $deletedItems,
            $itemDays,
        ));

        return self::SUCCESS;
    }
}
