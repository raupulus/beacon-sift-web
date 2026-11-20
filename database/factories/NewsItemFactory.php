<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Models\NewsItem;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsItem>
 */
class NewsItemFactory extends Factory
{
    protected $model = NewsItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priority = fake()->randomElement(Priority::cases());
        $url = fake()->unique()->url();
        $externalId = fake()->unique()->uuid();

        return [
            'source_id' => Source::factory(),
            'external_id' => $externalId,
            'fingerprint' => hash('sha256', $externalId.'|'.$url),
            'title' => fake()->sentence(8),
            'url' => $url,
            'snippet' => fake()->text(150),
            'image_url' => null,
            'author' => fake()->optional()->name(),
            'type' => fake()->randomElement(['cve', 'linux', 'debian', 'hardware', 'local']),
            'priority' => $priority,
            'priority_weight' => $priority->weight(),
            'tags' => ['news'],
            'metadata' => null,
            'published_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'fetched_at' => now(),
        ];
    }
}
