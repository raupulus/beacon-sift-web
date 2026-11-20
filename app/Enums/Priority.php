<?php

namespace App\Enums;

/**
 * Niveles de prioridad de fuentes y noticias en beacon-sift.
 * Incluye peso numérico determinista para filtros `min_priority`.
 */
enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * Devuelve el peso numérico ordenable de la prioridad.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }

    /**
     * Etiqueta legible para la interfaz web.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Critical => 'Crítica',
        };
    }

    /**
     * Clases Tailwind CSS v4 para el badge de prioridad según DESIGN.md.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-500/15 text-slate-300 border-slate-700',
            self::Medium => 'bg-sky-400/15 text-sky-300 border-sky-600/60',
            self::High => 'bg-amber-500/15 text-amber-300 border-amber-500/60',
            self::Critical => 'bg-red-500/15 text-red-400 border-red-500/70',
        };
    }

    /**
     * Resuelve un texto externo de forma segura o devuelve null si no es válido.
     */
    public static function tryFromNormalized(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }

    /**
     * Catálogo serializable para la API (/api/v1/types).
     *
     * @return list<array{value: string, label: string, weight: int}>
     */
    public static function catalog(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'weight' => $case->weight(),
            ],
            self::cases(),
        );
    }
}
