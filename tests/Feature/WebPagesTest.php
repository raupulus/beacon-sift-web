<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Models\NewsItem;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_cards_and_applies_url_filters(): void
    {
        $source = Source::factory()->create(['slug' => 'debian-feed', 'name' => 'Debian Feed', 'type' => 'debian']);

        NewsItem::factory()->create([
            'source_id' => $source->id,
            'title' => 'Debian 13 point release available',
            'type' => 'debian',
            'priority' => Priority::High,
            'priority_weight' => Priority::High->weight(),
            'image_url' => 'https://cdn.example.org/debian.jpg',
        ]);

        NewsItem::factory()->create([
            'source_id' => $source->id,
            'title' => 'Unrelated hardware benchmark',
            'type' => 'hardware',
            'priority' => Priority::Low,
            'priority_weight' => Priority::Low->weight(),
        ]);

        $this->get('/?type=debian&priority=high&q=Debian')
            ->assertOk()
            ->assertSee('Debian 13 point release available')
            ->assertSee('https://cdn.example.org/debian.jpg')
            ->assertSee('Mostrando 1 de 1 entradas')
            ->assertDontSee('Unrelated hardware benchmark');
    }

    public function test_home_paginates_chronologically(): void
    {
        $source = Source::factory()->create(['type' => 'linux']);

        NewsItem::factory()->count(20)->create([
            'source_id' => $source->id,
            'type' => 'linux',
        ]);

        $this->get('/?page=2')
            ->assertOk()
            ->assertSee('Mostrando 2 de 20 entradas')
            ->assertSee('Página 2 de 2');
    }

    public function test_sources_page_lists_and_filters_active_sources(): void
    {
        Source::factory()->create(['name' => 'Visible CVE Source', 'type' => 'cve', 'is_active' => true]);
        Source::factory()->create(['name' => 'Visible Linux Source', 'type' => 'linux', 'is_active' => true]);
        Source::factory()->create(['name' => 'Inactive Source', 'type' => 'cve', 'is_active' => false]);

        $this->get('/sources')
            ->assertOk()
            ->assertSee('Visible CVE Source')
            ->assertSee('Visible Linux Source')
            ->assertDontSee('Inactive Source');

        $this->get('/sources?type=cve')
            ->assertOk()
            ->assertSee('Visible CVE Source')
            ->assertDontSee('Visible Linux Source');
    }

    public function test_rss_feed_returns_valid_xml_with_filters_and_safe_cdata_escaping(): void
    {
        $source = Source::factory()->create(['slug' => 'cve-feed', 'name' => 'Security & Advisories', 'type' => 'cve']);
        NewsItem::factory()->create([
            'source_id' => $source->id,
            'title' => 'CVE-2026-0001 Critical Advisory ]]> <tag> & more',
            'url' => 'https://example.org/advisory?id=1&ref=rss',
            'snippet' => 'Detail ]]> with <unsafe> & chars',
            'type' => 'cve',
            'priority' => Priority::Critical,
            'priority_weight' => Priority::Critical->weight(),
        ]);

        $response = $this->get('/feed.rss?type=cve&min_priority=high');

        $response->assertOk();
        $this->assertStringContainsString('application/rss+xml', (string) $response->headers->get('Content-Type'));
        $response->assertSee('CVE-2026-0001 Critical Advisory', false);

        $xml = simplexml_load_string((string) $response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('CVE-2026-0001 Critical Advisory ]]> <tag> & more', (string) $xml->channel->item[0]->title);
    }

    public function test_static_legal_pages_render_custom_404_and_auth_admin_routes_do_not_exist(): void
    {
        $this->get('/about')->assertOk()->assertSee('public@raupulus.dev');
        $this->get('/privacy')->assertOk()->assertSee('public@raupulus.dev');
        $this->get('/cookies')->assertOk()->assertSee('public@raupulus.dev');

        $this->get('/ruta-que-no-existe-404')
            ->assertNotFound()
            ->assertSee('Señal no encontrada')
            ->assertSee('Volver al feed');

        $this->get('/admin')->assertNotFound();
        $this->get('/login')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }
}
