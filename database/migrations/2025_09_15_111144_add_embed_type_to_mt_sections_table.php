<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável por adicionar o tipo de incorporação às
 * secções das MetalThursdays.
 *
 * O nome físico da tabela e da coluna permanece temporariamente em inglês
 * para garantir compatibilidade com a estrutura atual da base de dados.
 *
 * @return Migration - Migração da coluna do tipo de incorporação.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Adiciona a coluna do tipo de incorporação à tabela das secções.
     *
     * A coluna é anulável porque nem todas as secções possuem um conteúdo
     * incorporado.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'mt_sections',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('embed_type')
                    ->nullable();
            },
        );
    }

    /**
     * Remove a coluna do tipo de incorporação da tabela das secções.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'mt_sections',
            function (Blueprint $tabela): void {
                $tabela->dropColumn('embed_type');
            },
        );
    }
};
