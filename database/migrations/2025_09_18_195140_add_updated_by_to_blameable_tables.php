<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração para adicionar a coluna 'updated_by' aos modelos 'bands', 'genres', 'mt_editions', 'metal_thursdays' e 'mt_sections'.
 *
 * @return Migration - Migração para adicionar a coluna 'updated_by' aos modelos 'bands', 'genres', 'mt_editions', 'metal_thursdays' e 'mt_sections'.
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
        $tables = ['bands', 'genres', 'mt_editions', 'metal_thursdays', 'mt_sections'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            });
        }
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
        $tables = ['bands', 'genres', 'mt_editions', 'metal_thursdays', 'mt_sections'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            });
        }
    }
};
