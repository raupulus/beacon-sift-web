<?php

namespace App\Models;

use App\Enums\Priority;
use Carbon\Carbon;
use Database\Factories\NewsItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Modelo de noticia o alerta normalizada y deduplicada en beacon-sift.
 *
 * @property int $id
 * @property int $source_id
 * @property string|null $external_id
 * @property string $fingerprint
 * @property string $title
 * @property string $url
 * @property string|null $snippet
 * @property string|null $image_url
 * @property string|null $author
 * @property string $type
 * @property Priority $priority
 * @property int $priority_weight
 * @property array<int, string>|null $tags
 * @property array<string, mixed>|null $metadata
 * @property Carbon $published_at
 * @property Carbon $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Source $source
 */
class NewsItem extends Model
{
    /** @use HasFactory<NewsItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'external_id',
        'fingerprint',
        'title',
        'url',
        'snippet',
        'image_url',
        'author',
        'type',
        'priority',
        'priority_weight',
        'tags',
        'metadata',
        'published_at',
        'fetched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'priority_weight' => 'integer',
            'tags' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Fuente de origen de esta noticia.
     *
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Orden cronológico descendente estable (últimas noticias primero).
     *
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Filtra por uno o varios tipos separados por coma.
     *
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        if ($type === null || trim($type) === '') {
            return $query;
        }

        $types = array_values(array_filter(array_map(
            static fn (string $t): string => strtolower(trim($t)),
            explode(',', $type),
        )));

        if ($types === []) {
            return $query;
        }

        return count($types) === 1
            ? $query->where('type', $types[0])
            : $query->whereIn('type', $types);
    }

    /**
     * Filtra por prioridad mínima (>= peso).
     *
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeFilterByMinPriority(Builder $query, ?string $minPriority): Builder
    {
        $priority = Priority::tryFromNormalized($minPriority);

        if ($priority === null) {
            return $query;
        }

        return $query->where('priority_weight', '>=', $priority->weight());
    }

    /**
     * Filtra por prioridad exacta.
     *
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeFilterByPriority(Builder $query, ?string $priorityValue): Builder
    {
        $priority = Priority::tryFromNormalized($priorityValue);

        if ($priority === null) {
            return $query;
        }

        return $query->where('priority', $priority->value);
    }

    /**
     * Filtra por slug de fuente.
     *
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeFilterBySourceSlug(Builder $query, ?string $slug): Builder
    {
        if ($slug === null || trim($slug) === '') {
            return $query;
        }

        $normalized = strtolower(trim($slug));

        return $query->whereHas('source', static function (Builder $builder) use ($normalized): void {
            $builder->where('slug', $normalized);
        });
    }

    /**
     * Busca palabras clave en título y extracto (FTS/ILIKE en PostgreSQL, LIKE en SQLite).
     *
     * @param  Builder<NewsItem>  $query
     * @return Builder<NewsItem>
     */
    public function scopeSearchQuery(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $clean = trim($term);
        $like = '%'.$clean.'%';

        if (DB::getDriverName() === 'pgsql') {
            return $query->where(function (Builder $builder) use ($clean, $like): void {
                $builder->whereRaw(
                    "to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(snippet, '')) @@ plainto_tsquery('simple', ?)",
                    [$clean],
                )
                    ->orWhere('title', 'ILIKE', $like)
                    ->orWhere('snippet', 'ILIKE', $like);
            });
        }

        return $query->where(function (Builder $builder) use ($like): void {
            $builder->where('title', 'like', $like)
                ->orWhere('snippet', 'like', $like);
        });
    }

    /**
     * Calcula el hash SHA-256 determinista para deduplicar entradas por fuente.
     */
    public static function makeFingerprint(int $sourceId, ?string $externalId, string $url): string
    {
        $identity = ($externalId !== null && trim($externalId) !== '')
            ? trim($externalId)
            : trim($url);

        return hash('sha256', $sourceId.'|'.$identity);
    }
}
