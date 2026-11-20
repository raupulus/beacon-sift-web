<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ruta del archivo JSON de fuentes
    |--------------------------------------------------------------------------
    |
    | Archivo declarativo que define las fuentes de ingesta. En el repositorio
    | se versiona sources.example.json y el archivo real queda ignorado en Git.
    |
    */
    'sources_path' => env('BEACON_SOURCES_PATH', storage_path('app/sources.json')),

    /*
    |--------------------------------------------------------------------------
    | Retención de registros (en días; 0 = sin borrado automático)
    |--------------------------------------------------------------------------
    */
    'fetch_logs_retention_days' => (int) env('BEACON_FETCH_LOGS_RETENTION_DAYS', 30),
    'items_retention_days' => (int) env('BEACON_ITEMS_RETENTION_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | Opciones por defecto de ingesta HTTP y agregación responsable
    |--------------------------------------------------------------------------
    */
    'default_snippet_max_length' => (int) env('BEACON_SNIPPET_MAX_LENGTH', 160),
    'default_timeout_seconds' => (int) env('BEACON_HTTP_TIMEOUT', 15),
    'default_interval_minutes' => (int) env('BEACON_DEFAULT_INTERVAL_MINUTES', 30),
    'user_agent' => env('BEACON_USER_AGENT', 'beacon-sift/1.0 (+https://feed.raupulus.dev)'),

    /*
    |--------------------------------------------------------------------------
    | Difusión en tiempo real (WebSockets públicos)
    |--------------------------------------------------------------------------
    */
    'broadcast_channel' => env('BEACON_BROADCAST_CHANNEL', 'beacon.news'),

    /*
    |--------------------------------------------------------------------------
    | Contacto público para avisos legales y retirada de fuentes
    |--------------------------------------------------------------------------
    */
    'contact_email' => env('BEACON_CONTACT_EMAIL', 'public@raupulus.dev'),
];
