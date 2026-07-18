<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração para adicionar a coluna embed_type na tabela mt_sections.
 *
 * @return Migration - Migração para adicionar a coluna embed_type na tabela mt_sections.
 *
 * @since 1.0
 * @version 1.0
 */
return new class extends Migration
{
    /**
     * Executa a migração.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function up(): void
    {
        Schema::table('mt_sections', function (Blueprint $table) {
            $table->string('embed_type')->nullable()->after('link');
        });
    }

    /**
     * Reverte a migração.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function down(): void
    {
        Schema::table('mt_sections', function (Blueprint $table) {
            $table->dropColumn('embed_type');
        });
    }
};
