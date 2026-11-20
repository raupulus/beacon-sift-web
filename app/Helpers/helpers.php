<?php

/**
 * Funciones helper globales disponibles en toda la aplicación.
 * Registradas automáticamente mediante composer.json (autoload.files).
 */

use Carbon\Carbon;
use Carbon\CarbonInterface;

if (! function_exists('format_date')) {
    /**
     * Formatea de forma segura una fecha al formato indicado (por defecto d/m/Y H:i).
     */
    function format_date(mixed $date, string $format = 'd/m/Y H:i'): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            if ($date instanceof CarbonInterface) {
                return $date->timezone(config('app.timezone', 'Europe/Madrid'))->format($format);
            }

            return Carbon::parse($date)
                ->timezone(config('app.timezone', 'Europe/Madrid'))
                ->format($format);
        } catch (Throwable) {
            return (string) $date;
        }
    }
}
