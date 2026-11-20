<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de fuentes de noticias sincronizadas desde sources.json
     * junto con su estado operativo de caché HTTP y planificación de sondeo.
     */
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique()->comment('Identificador estable de la fuente (campo id en sources.json)');
            $table->string('name', 255)->comment('Nombre visible de la fuente');
            $table->text('url')->comment('URL pública del feed o endpoint de origen');
            $table->string('format', 32)->index()->comment('Formato del adaptador: rss, atom, json, cve_nvd');
            $table->string('type', 64)->index()->comment('Categoría o dominio temático (cve, linux, local, etc.)');
            $table->string('priority', 16)->index()->comment('Prioridad nominal: low, medium, high, critical');
            $table->unsignedSmallInteger('priority_weight')->index()->comment('Peso numérico de prioridad (1..4)');
            $table->unsignedInteger('interval_minutes')->default(30)->comment('Intervalo mínimo en minutos entre comprobaciones');
            $table->string('language', 10)->nullable()->comment('Código de idioma ISO 639-1 opcional');
            $table->json('tags')->nullable()->comment('Etiquetas adicionales de la fuente');
            $table->json('adapter_options')->nullable()->comment('Opciones específicas del adaptador (p. ej. mapeo JSON)');
            $table->unsignedSmallInteger('snippet_max_length')->default(160)->comment('Longitud máxima del extracto permitido');
            $table->boolean('is_active')->default(true)->index()->comment('Indica si la fuente está activa en ingesta y listados');
            $table->boolean('managed_by_json')->default(true)->comment('Indica si se gestiona mediante sources.json');

            // Estado de caché HTTP y programación de sondeo
            $table->string('etag', 255)->nullable()->comment('Última cabecera HTTP ETag recibida');
            $table->string('last_modified', 255)->nullable()->comment('Última cabecera HTTP Last-Modified recibida');
            $table->timestampTz('last_fetched_at')->nullable()->comment('Fecha de la última comprobación');
            $table->timestampTz('next_fetch_at')->nullable()->index()->comment('Fecha programada para la siguiente comprobación');
            $table->timestampTz('last_success_at')->nullable()->comment('Fecha de la última ingesta exitosa');
            $table->unsignedSmallInteger('last_status_code')->nullable()->comment('Último código de estado HTTP');
            $table->text('last_error')->nullable()->comment('Detalle del último error de ingesta si existió');
            $table->unsignedInteger('consecutive_failures')->default(0)->comment('Número de fallos consecutivos');

            $table->timestampsTz();
        });
    }

    /**
     * Revierte la creación de la tabla sources.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
