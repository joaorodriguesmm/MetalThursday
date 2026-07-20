<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável por normalizar os nomes das tabelas de
 * interação da aplicação.
 *
 * Esta migração remove o prefixo `mt_` dos nomes físicos das tabelas de
 * comentários, gostos, classificações e audições.
 *
 * @return Migration - Migração de renomeação das tabelas de interação.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Renomeia as tabelas de interação.
     *
     * As tabelas são renomeadas depois de terem sido criadas pelas respetivas
     * migrações anteriores.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::rename(
            'mt_comments',
            'comments',
        );

        Schema::rename(
            'mt_likes',
            'likes',
        );

        Schema::rename(
            'mt_ratings',
            'ratings',
        );

        Schema::rename(
            'mt_listens',
            'listens',
        );
    }

    /**
     * Repõe os nomes anteriores das tabelas de interação.
     *
     * A ordem inversa garante que o estado anterior à migração é restaurado
     * de forma previsível.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::rename(
            'listens',
            'mt_listens',
        );

        Schema::rename(
            'ratings',
            'mt_ratings',
        );

        Schema::rename(
            'likes',
            'mt_likes',
        );

        Schema::rename(
            'comments',
            'mt_comments',
        );
    }
};
