<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Services\Ingestion\FeedIngestionService;
use App\Services\SourceSyncService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Ejecuta la sincronización automática de sources.json (si cambió) e ingesta las fuentes pendientes.
 */
class FetchFeedsCommand extends Command
{
    protected $signature = 'feeds:fetch
        {--source= : Slug de una fuente específica a ingestar}
        {--force : Ignora next_fetch_at y fuerza la comprobación de todas las fuentes activas}';

    protected $description = 'Sincroniza sources.json si cambió e ingesta las fuentes activas pendientes';

    public function handle(SourceSyncService $syncService, FeedIngestionService $ingestionService): int
    {
        try {
            $syncResult = $syncService->syncIfChanged();
            if ($syncResult['synced']) {
                $this->info(sprintf(
                    'sources.json actualizado: %d creadas, %d actualizadas, %d desactivadas.',
                    $syncResult['created'],
                    $syncResult['updated'],
                    $syncResult['deactivated'],
                ));
            }
        } catch (Throwable $e) {
            $this->warn('No se pudo auto-sincronizar sources.json: '.$e->getMessage());
        }

        $sourceSlug = $this->option('source');
        $force = (bool) $this->option('force');

        $query = Source::query();

        if (is_string($sourceSlug) && trim($sourceSlug) !== '') {
            $query->where('slug', trim($sourceSlug));
        } elseif ($force) {
            $query->active();
        } else {
            $query->dueForFetch();
        }

        $sources = $query->orderBy('id')->get();

        if ($sources->isEmpty()) {
            $this->line('No hay fuentes pendientes de comprobación.');

            return self::SUCCESS;
        }

        $totalCreated = 0;

        foreach ($sources as $source) {
            $result = $ingestionService->ingest($source);
            $totalCreated += $result['created'];

            $this->line(sprintf(
                '[%s] %s — encontradas: %d, nuevas: %d%s',
                $source->slug,
                $result['status']->value,
                $result['found'],
                $result['created'],
                $result['error'] !== null ? ' | error: '.$result['error'] : '',
            ));
        }

        $this->info("Ingesta finalizada. Nuevas entradas añadidas: {$totalCreated}.");

        return self::SUCCESS;
    }
}
