<?php

namespace Database\Factories;

use App\Enums\FetchStatus;
use App\Models\Source;
use App\Models\SourceFetchLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourceFetchLog>
 */
class SourceFetchLogFactory extends Factory
{
    protected $model = SourceFetchLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'status' => FetchStatus::Success,
            'http_status' => 200,
            'items_found' => fake()->numberBetween(1, 30),
            'items_created' => fake()->numberBetween(0, 10),
            'duration_ms' => fake()->numberBetween(50, 900),
            'error_message' => null,
            'fetched_at' => now(),
        ];
    }
}
