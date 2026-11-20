<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Models\NewsItem;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_returns_health_and_websocket_metadata(): void
    {
        Source::factory()->create(['is_active' => true]);

        $this->getJson('/api/v1/status')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('database', 'ok')
            ->assertJsonPath('sources.active', 1)
            ->assertJsonPath('websocket.channel', 'beacon.news')
            ->assertJsonPath('websocket.event', 'news.created');
    }

    public function test_types_and_sources_endpoints_return_active_catalog(): void
    {
        $cveSource = Source::factory()->create([
            'slug' => 'debian-cve',
            'type' => 'cve',
            'priority' => Priority::High,
            'priority_weight' => Priority::High->weight(),
            'is_active' => true,
        ]);

        NewsItem::factory()->count(2)->create([
            'source_id' => $cveSource->id,
            'type' => 'cve',
        ]);

        $this->getJson('/api/v1/types')
            ->assertOk()
            ->assertJsonPath('types.0.type', 'cve')
            ->assertJsonPath('types.0.sources_count', 1)
            ->assertJsonPath('types.0.items_count', 2)
            ->assertJsonCount(4, 'priorities');

        $this->getJson('/api/v1/sources?type=cve')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'debian-cve');
    }

    public function test_items_endpoint_supports_type_min_priority_search_and_since_id(): void
    {
        $source = Source::factory()->create(['slug' => 'sec-feed', 'type' => 'cve']);

        $lowItem = NewsItem::factory()->create([
            'source_id' => $source->id,
            'title' => 'Minor documentation note',
            'type' => 'cve',
            'priority' => Priority::Low,
            'priority_weight' => Priority::Low->weight(),
            'published_at' => now()->subHours(2),
        ]);

        $criticalItem = NewsItem::factory()->create([
            'source_id' => $source->id,
            'title' => 'Critical OpenSSL zero-day vulnerability',
            'type' => 'cve',
            'priority' => Priority::Critical,
            'priority_weight' => Priority::Critical->weight(),
            'published_at' => now()->subMinute(),
        ]);

        $this->getJson('/api/v1/items?type=cve&min_priority=high&q=OpenSSL')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $criticalItem->id)
            ->assertJsonPath('data.0.source.slug', 'sec-feed');

        $this->getJson('/api/v1/items?since_id='.$lowItem->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $criticalItem->id);
    }

    public function test_items_endpoint_clamps_per_page_ignores_invalid_priority_supports_csv_types_and_rate_limits(): void
    {
        $cveSource = Source::factory()->create(['slug' => 'cve-src', 'type' => 'cve']);
        $linuxSource = Source::factory()->create(['slug' => 'linux-src', 'type' => 'linux']);
        $otherSource = Source::factory()->create(['slug' => 'hw-src', 'type' => 'hardware']);

        $first = NewsItem::factory()->create([
            'source_id' => $cveSource->id,
            'type' => 'cve',
            'priority' => Priority::High,
            'priority_weight' => Priority::High->weight(),
            'published_at' => now()->subMinutes(10),
        ]);
        $second = NewsItem::factory()->create([
            'source_id' => $linuxSource->id,
            'type' => 'linux',
            'priority' => Priority::Medium,
            'priority_weight' => Priority::Medium->weight(),
            'published_at' => now()->subMinutes(5),
        ]);
        NewsItem::factory()->create([
            'source_id' => $otherSource->id,
            'type' => 'hardware',
            'priority' => Priority::Low,
            'priority_weight' => Priority::Low->weight(),
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/v1/items?type=cve,linux&priority=invalido&per_page=999&order=asc&since_id='.($first->id - 1));

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertJsonPath('per_page', 50)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id);
    }
}
