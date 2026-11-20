<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\SourceFormat;
use App\Models\Source;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sincroniza el archivo declarativo sources.json hacia la tabla sources en PostgreSQL.
 * Desactiva las fuentes retiradas del JSON sin eliminar el histórico de noticias.
 */
class SourceSyncService
{
    private const string HASH_CACHE_KEY = 'beacon:sources_json_hash';

    /**
     * Sincroniza únicamente si el archivo JSON ha cambiado desde la última lectura.
     *
     * @return array{synced: bool, created: int, updated: int, deactivated: int}
     */
    public function syncIfChanged(?string $path = null): array
    {
        $resolvedPath = $path ?? (string) config('beacon.sources_path');

        if (! is_file($resolvedPath)) {
            return ['synced' => false, 'created' => 0, 'updated' => 0, 'deactivated' => 0];
        }

        $currentHash = hash_file('sha256', $resolvedPath);
        $cachedHash = Cache::get(self::HASH_CACHE_KEY);

        if ($cachedHash === $currentHash) {
            return ['synced' => false, 'created' => 0, 'updated' => 0, 'deactivated' => 0];
        }

        $result = $this->sync($resolvedPath);
        Cache::forever(self::HASH_CACHE_KEY, $currentHash);

        return ['synced' => true, ...$result];
    }

    /**
     * Ejecuta la sincronización completa del JSON hacia PostgreSQL.
     *
     * @return array{created: int, updated: int, deactivated: int}
     */
    public function sync(?string $path = null): array
    {
        $resolvedPath = $path ?? (string) config('beacon.sources_path');

        if (! is_file($resolvedPath)) {
            throw new RuntimeException("No se encontró el archivo de fuentes JSON en: {$resolvedPath}");
        }

        $raw = file_get_contents($resolvedPath);
        if ($raw === false) {
            throw new RuntimeException("No se pudo leer el archivo de fuentes JSON: {$resolvedPath}");
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! isset($decoded['sources']) || ! is_array($decoded['sources'])) {
            throw new RuntimeException('El archivo JSON de fuentes debe contener un array "sources".');
        }

        $defaults = is_array($decoded['defaults'] ?? null) ? $decoded['defaults'] : [];
        $defaultEnabled = (bool) ($defaults['enabled'] ?? true);
        $defaultPriority = Priority::tryFromNormalized((string) ($defaults['priority'] ?? 'medium')) ?? Priority::Medium;
        $defaultInterval = max(1, (int) ($defaults['interval_minutes'] ?? config('beacon.default_interval_minutes', 30)));
        $defaultSnippetMax = max(40, (int) ($defaults['snippet_max_length'] ?? config('beacon.default_snippet_max_length', 160)));

        $created = 0;
        $updated = 0;
        $seenSlugs = [];

        foreach ($decoded['sources'] as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $rawId = (string) ($entry['id'] ?? $entry['slug'] ?? '');
            $slug = Str::slug($rawId);
            $name = trim((string) ($entry['name'] ?? ''));
            $url = trim((string) ($entry['url'] ?? ''));

            if ($slug === '' || $name === '' || $url === '') {
                throw new RuntimeException("Fuente inválida en índice {$index}: id, name y url son obligatorios.");
            }

            $format = SourceFormat::tryFrom(strtolower(trim((string) ($entry['format'] ?? 'rss'))))
                ?? throw new RuntimeException("Formato no soportado en fuente '{$slug}'.");

            $priority = Priority::tryFromNormalized(isset($entry['priority']) ? (string) $entry['priority'] : null)
                ?? $defaultPriority;

            $type = Str::slug((string) ($entry['type'] ?? 'general'));
            if ($type === '') {
                $type = 'general';
            }

            $seenSlugs[] = $slug;

            $attributes = [
                'name' => $name,
                'url' => $url,
                'format' => $format,
                'type' => $type,
                'priority' => $priority,
                'priority_weight' => $priority->weight(),
                'interval_minutes' => max(1, (int) ($entry['interval_minutes'] ?? $defaultInterval)),
                'language' => isset($entry['language']) ? substr(trim((string) $entry['language']), 0, 10) : null,
                'tags' => is_array($entry['tags'] ?? null) ? array_values(array_map('strval', $entry['tags'])) : [],
                'adapter_options' => is_array($entry['adapter_options'] ?? null) ? $entry['adapter_options'] : [],
                'snippet_max_length' => max(40, (int) ($entry['snippet_max_length'] ?? $defaultSnippetMax)),
                'is_active' => array_key_exists('enabled', $entry) ? (bool) $entry['enabled'] : $defaultEnabled,
                'managed_by_json' => true,
            ];

            $existing = Source::query()->where('slug', $slug)->first();

            if ($existing === null) {
                Source::query()->create(['slug' => $slug, ...$attributes]);
                $created++;
            } else {
                $existing->fill($attributes)->save();
                $updated++;
            }
        }

        // Desactivar fuentes gestionadas por JSON que ya no estén en el archivo (conservando su histórico)
        $deactivatedQuery = Source::query()
            ->where('managed_by_json', true)
            ->where('is_active', true);

        if ($seenSlugs !== []) {
            $deactivatedQuery->whereNotIn('slug', $seenSlugs);
        }

        $deactivated = $deactivatedQuery->update(['is_active' => false]);

        $hash = hash_file('sha256', $resolvedPath);
        if ($hash !== false) {
            Cache::forever(self::HASH_CACHE_KEY, $hash);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'deactivated' => $deactivated,
        ];
    }
}
