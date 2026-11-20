<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Str;

/**
 * Utilidades de limpieza y validación para garantizar agregación responsable:
 * extractos breves sin HTML peligroso y URLs HTTP/HTTPS válidas.
 */
final class TextSanitizer
{
    /**
     * Limpia etiquetas HTML, entidades y espacios repetidos, truncando al máximo permitido.
     */
    public static function snippet(?string $raw, int $maxLength = 160): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = strip_tags($decoded);
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $plain));

        if ($normalized === '') {
            return null;
        }

        return Str::limit($normalized, max(40, $maxLength), '…');
    }

    /**
     * Valida que una URL sea HTTP o HTTPS bien formada.
     */
    public static function safeHttpUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $clean = trim($url);
        if (! filter_var($clean, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($clean, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $clean : null;
    }

    /**
     * Resuelve URLs relativas contra la URL base de la fuente y valida esquema HTTP/HTTPS.
     */
    public static function resolveUrl(?string $url, string $baseUrl): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $clean = trim($url);

        // Si trae esquema explícito, validar directamente que sea http o https
        if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $clean) === 1) {
            return self::safeHttpUrl($clean);
        }

        $baseParts = parse_url(trim($baseUrl));
        if (! is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $baseParts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $authority = $scheme.'://'.$baseParts['host'];
        if (isset($baseParts['port'])) {
            $authority .= ':'.$baseParts['port'];
        }

        if (str_starts_with($clean, '//')) {
            return self::safeHttpUrl($scheme.':'.$clean);
        }

        if (str_starts_with($clean, '/')) {
            return self::safeHttpUrl($authority.$clean);
        }

        $basePath = (string) ($baseParts['path'] ?? '/');
        $directory = str_ends_with($basePath, '/') ? $basePath : rtrim(dirname($basePath), '/').'/';

        return self::safeHttpUrl($authority.$directory.$clean);
    }
}
