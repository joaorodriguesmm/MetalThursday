<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável por adicionar a ligação da compilação às
 * edições das MetalThursdays.
 *
 * O nome físico da tabela e da coluna permanece temporariamente em inglês
 * para garantir compatibilidade com a estrutura atual da base de dados.
 *
 * @return Migration - Migração da ligação da compilação.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Adiciona a coluna da ligação da compilação à tabela das edições.
     *
     * O limite de 2048 caracteres permite armazenar ligações extensas sem
     * utilizar uma coluna de texto ilimitado.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'mt_editions',
            function (Blueprint $tabela): void {
                $tabela
                    ->string(
                        'compilation_link',
                        2048,
                    )
                    ->nullable();
            },
        );
    }

    /**
     * Remove a coluna da ligação da compilação da tabela das edições.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'mt_editions',
            function (Blueprint $tabela): void {
                $tabela->dropColumn(
                    'compilation_link',
                );
            },
        );
    }
};
