<?php

namespace App\Services\Ingestion;

use App\Models\Source;

/**
 * Contrato común para todos los adaptadores de formato (RSS, Atom, JSON, CVE).
 */
interface FeedAdapterInterface
{
    /**
     * Parsea el cuerpo crudo recibido del origen y devuelve entradas normalizadas.
     *
     * @return list<ParsedFeedItem>
     */
    public function parse(string $body, Source $source): array;
}
