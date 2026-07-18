<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração consolidada para renomear as tabelas de interação.
 *
 * @return Migration - Migração consolidada.
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
        Schema::rename('mt_comments', 'comments');
        Schema::rename('mt_likes', 'likes');
        Schema::rename('mt_ratings', 'ratings');
        Schema::rename('mt_listens', 'listens');
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
        Schema::rename('comments', 'mt_comments');
        Schema::rename('likes', 'mt_likes');
        Schema::rename('ratings', 'mt_ratings');
        Schema::rename('listens', 'mt_listens');
    }
};
