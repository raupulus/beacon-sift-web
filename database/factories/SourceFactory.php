<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\SourceFormat;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    protected $model = Source::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Feed';
        $priority = fake()->randomElement(Priority::cases());

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'name' => $name,
            'url' => fake()->url(),
            'format' => SourceFormat::Rss,
            'type' => fake()->randomElement(['cve', 'linux', 'debian', 'hardware', 'local']),
            'priority' => $priority,
            'priority_weight' => $priority->weight(),
            'interval_minutes' => 30,
            'language' => 'es',
            'tags' => ['tech'],
            'adapter_options' => [],
            'snippet_max_length' => 160,
            'is_active' => true,
            'managed_by_json' => true,
            'consecutive_failures' => 0,
        ];
    }
}
