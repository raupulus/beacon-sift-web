<?php

namespace App\Console\Commands;

use App\Services\SourceSyncService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sincroniza el archivo declarativo sources.json hacia la tabla sources en PostgreSQL.
 */
class SyncSourcesCommand extends Command
{
    protected $signature = 'sources:sync {--path= : Ruta alternativa al archivo JSON de fuentes}';

    protected $description = 'Sincroniza las fuentes definidas en sources.json con PostgreSQL';

    public function handle(SourceSyncService $syncService): int
    {
        $path = $this->option('path');

        try {
            $result = $syncService->sync(is_string($path) && $path !== '' ? $path : null);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sincronización completada: %d creadas, %d actualizadas, %d desactivadas.',
            $result['created'],
            $result['updated'],
            $result['deactivated'],
        ));

        return self::SUCCESS;
    }
}
