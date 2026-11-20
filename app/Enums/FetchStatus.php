<?php

namespace App\Enums;

/**
 * Resultado de una ejecución de ingesta de una fuente.
 */
enum FetchStatus: string
{
    case Success = 'success';
    case NotModified = 'not_modified';
    case Error = 'error';
}
