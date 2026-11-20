<?php

namespace App\Models;

use App\Enums\FetchStatus;
use Carbon\Carbon;
use Database\Factories\SourceFetchLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoría de cada ejecución de ingesta por fuente.
 *
 * @property int $id
 * @property int $source_id
 * @property FetchStatus $status
 * @property int|null $http_status
 * @property int $items_found
 * @property int $items_created
 * @property int|null $duration_ms
 * @property string|null $error_message
 * @property Carbon $fetched_at
 * @property-read Source $source
 */
class SourceFetchLog extends Model
{
    /** @use HasFactory<SourceFetchLogFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'status',
        'http_status',
        'items_found',
        'items_created',
        'duration_ms',
        'error_message',
        'fetched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FetchStatus::class,
            'http_status' => 'integer',
            'items_found' => 'integer',
            'items_created' => 'integer',
            'duration_ms' => 'integer',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Fuente asociada al registro de ingesta.
     *
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
