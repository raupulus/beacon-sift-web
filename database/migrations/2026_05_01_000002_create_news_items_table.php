<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de entradas normalizadas y deduplicadas (agregación responsable).
     */
    public function up(): void
    {
        Schema::create('news_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->constrained('sources')->cascadeOnDelete()->comment('Fuente de origen de la entrada');
            $table->string('external_id', 512)->nullable()->comment('Identificador original en el feed (guid, id, CVE ID)');
            $table->char('fingerprint', 64)->unique()->comment('Hash SHA-256 determinista para deduplicación');
            $table->string('title', 500)->comment('Titular de la noticia o alerta');
            $table->text('url')->comment('Enlace directo al artículo original');
            $table->text('snippet')->nullable()->comment('Extracto breve limpio (sin HTML ni cuerpo completo)');
            $table->text('image_url')->nullable()->comment('URL externa de imagen del feed para hotlink (sin re-alojar)');
            $table->string('author', 255)->nullable()->comment('Autor declarado en el feed si existe');
            $table->string('type', 64)->index()->comment('Tipo temático normalizado para filtrado rápido');
            $table->string('priority', 16)->index()->comment('Prioridad nominal de la entrada');
            $table->unsignedSmallInteger('priority_weight')->index()->comment('Peso numérico de prioridad (1..4)');
            $table->json('tags')->nullable()->comment('Etiquetas asociadas a la entrada');
            $table->json('metadata')->nullable()->comment('Metadatos estructurados adicionales (p. ej. cvss_score)');
            $table->timestampTz('published_at')->index()->comment('Fecha real de publicación en origen');
            $table->timestampTz('fetched_at')->index()->comment('Fecha de ingesta en beacon-sift');
            $table->timestampsTz();

            $table->index(['published_at', 'id'], 'news_items_published_id_idx');
            $table->index(['type', 'priority_weight', 'published_at'], 'news_items_type_priority_published_idx');
            $table->index(['source_id', 'published_at'], 'news_items_source_published_idx');
        });

        // Índice GIN de búsqueda de texto completo nativo en PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE INDEX news_items_fts_idx ON news_items USING GIN (to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(snippet, '')))"
            );
        }
    }

    /**
     * Revierte la creación de la tabla news_items.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
