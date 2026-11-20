<?php

namespace Tests\Feature;

use App\Models\NewsItem;
use App\Models\Source;
use App\Services\SourceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SourceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_sources_from_json_and_deactivates_removed_ones_without_deleting_history(): void
    {
        $tempPath = storage_path('framework/testing/sources-sync-test.json');
        @mkdir(dirname($tempPath), 0777, true);

        file_put_contents($tempPath, json_encode([
            'version' => '1.0',
            'defaults' => [
                'priority' => 'medium',
                'interval_minutes' => 30,
            ],
            'sources' => [
                [
                    'id' => 'debian-security',
                    'name' => 'Debian Security',
                    'url' => 'https://www.debian.org/security/dsa',
                    'format' => 'rss',
                    'type' => 'cve',
                    'priority' => 'high',
                ],
                [
                    'id' => 'linux-kernel',
                    'name' => 'Linux Kernel',
                    'url' => 'https://www.kernel.org/feeds/kdist.xml',
                    'format' => 'rss',
                    'type' => 'linux',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('sources:sync', ['--path' => $tempPath])
            ->assertSuccessful();

        $this->assertDatabaseCount('sources', 2);

        $linuxSource = Source::query()->where('slug', 'linux-kernel')->firstOrFail();
        NewsItem::factory()->create(['source_id' => $linuxSource->id, 'type' => 'linux']);

        // Quitamos linux-kernel del JSON: debe desactivarse sin borrar sus noticias
        file_put_contents($tempPath, json_encode([
            'version' => '1.0',
            'sources' => [
                [
                    'id' => 'debian-security',
                    'name' => 'Debian Security Updated',
                    'url' => 'https://www.debian.org/security/dsa',
                    'format' => 'rss',
                    'type' => 'cve',
                    'priority' => 'critical',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('sources:sync', ['--path' => $tempPath])
            ->assertSuccessful();

        $this->assertDatabaseHas('sources', [
            'slug' => 'debian-security',
            'name' => 'Debian Security Updated',
            'priority' => 'critical',
            'priority_weight' => 4,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('sources', [
            'slug' => 'linux-kernel',
            'is_active' => false,
        ]);

        // El histórico de noticias de la fuente desactivada se conserva intacto
        $this->assertDatabaseCount('news_items', 1);

        @unlink($tempPath);
    }

    public function test_sync_if_changed_skips_when_file_hash_is_unchanged(): void
    {
        Cache::flush();
        $tempPath = storage_path('framework/testing/sources-hash-test.json');
        @mkdir(dirname($tempPath), 0777, true);

        file_put_contents($tempPath, json_encode([
            'sources' => [
                [
                    'id' => 'sample-feed',
                    'name' => 'Sample Feed',
                    'url' => 'https://example.org/rss.xml',
                    'format' => 'rss',
                    'type' => 'linux',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $service = app(SourceSyncService::class);

        $first = $service->syncIfChanged($tempPath);
        $second = $service->syncIfChanged($tempPath);

        $this->assertTrue($first['synced']);
        $this->assertSame(1, $first['created']);
        $this->assertFalse($second['synced']);

        @unlink($tempPath);
    }
}
