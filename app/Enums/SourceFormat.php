<?php

namespace App\Enums;

/**
 * Formatos de ingesta soportados por los adaptadores de beacon-sift.
 */
enum SourceFormat: string
{
    case Rss = 'rss';
    case Atom = 'atom';
    case Json = 'json';
    case CveNvd = 'cve_nvd';

    /**
     * Etiqueta legible del formato de fuente.
     */
    public function label(): string
    {
        return match ($this) {
            self::Rss => 'RSS',
            self::Atom => 'Atom',
            self::Json => 'JSON',
            self::CveNvd => 'CVE (NVD)',
        };
    }
}
