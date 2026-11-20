<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\SourceFormat;
use Carbon\Carbon;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de fuente configurada para ingesta en beacon-sift.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $url
 * @property SourceFormat $format
 * @property string $type
 * @property Priority $priority
 * @property int $priority_weight
 * @property int $interval_minutes
 * @property string|null $language
 * @property array<int, string>|null $tags
 * @property array<string, mixed>|null $adapter_options
 * @property int $snippet_max_length
 * @property bool $is_active
 * @property bool $managed_by_json
 * @property string|null $etag
 * @property string|null $last_modified
 * @property Carbon|null $last_fetched_at
 * @property Carbon|null $next_fetch_at
 * @property Carbon|null $last_success_at
 * @property int|null $last_status_code
 * @property string|null $last_error
 * @property int $consecutive_failures
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'url',
        'format',
        'type',
        'priority',
        'priority_weight',
        'interval_minutes',
        'language',
        'tags',
        'adapter_options',
        'snippet_max_length',
        'is_active',
        'managed_by_json',
        'etag',
        'last_modified',
        'last_fetched_at',
        'next_fetch_at',
        'last_success_at',
        'last_status_code',
        'last_error',
        'consecutive_failures',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'format' => SourceFormat::class,
            'priority' => Priority::class,
            'priority_weight' => 'integer',
            'interval_minutes' => 'integer',
            'tags' => 'array',
            'adapter_options' => 'array',
            'snippet_max_length' => 'integer',
            'is_active' => 'boolean',
            'managed_by_json' => 'boolean',
            'last_fetched_at' => 'datetime',
            'next_fetch_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_status_code' => 'integer',
            'consecutive_failures' => 'integer',
        ];
    }

    /**
     * Noticias ingestadas pertenecientes a esta fuente.
     *
     * @return HasMany<NewsItem, $this>
     */
    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    /**
     * Registros de ejecuciones de ingesta de esta fuente.
     *
     * @return HasMany<SourceFetchLog, $this>
     */
    public function fetchLogs(): HasMany
    {
        return $this->hasMany(SourceFetchLog::class);
    }

    /**
     * Filtra únicamente fuentes activas.
     *
     * @param  Builder<Source>  $query
     * @return Builder<Source>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra fuentes activas pendientes de comprobación según su intervalo.
     *
     * @param  Builder<Source>  $query
     * @return Builder<Source>
     */
    public function scopeDueForFetch(Builder $query): Builder
    {
        return $query->active()->where(function (Builder $builder): void {
            $builder->whereNull('next_fetch_at')
                ->orWhere('next_fetch_at', '<=', now());
        });
    }
}
