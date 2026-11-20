<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de auditoría de ingestas por fuente (purga automática configurable, 30 días por defecto).
     */
    public function up(): void
    {
        Schema::create('source_fetch_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->cascadeOnDelete()->comment('Fuente comprobada');
            $table->string('status', 24)->index()->comment('Resultado: success, not_modified o error');
            $table->unsignedSmallInteger('http_status')->nullable()->comment('Código HTTP de la respuesta');
            $table->unsignedInteger('items_found')->default(0)->comment('Total de ítems leídos del origen');
            $table->unsignedInteger('items_created')->default(0)->comment('Nuevos ítems insertados tras deduplicar');
            $table->unsignedInteger('duration_ms')->nullable()->comment('Duración de la ingesta en milisegundos');
            $table->text('error_message')->nullable()->comment('Mensaje de error si status = error');
            $table->timestampTz('fetched_at')->index()->comment('Instante de la ejecución');
        });
    }

    /**
     * Revierte la creación de la tabla source_fetch_logs.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_fetch_logs');
    }
};
