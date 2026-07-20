<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação da tabela das classificações
 * submetidas para as edições.
 *
 * Os nomes físicos da tabela e das colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração da tabela das classificações das edições.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das classificações submetidas para as edições.
     *
     * Cada registo associa uma entrada textual a uma edição e a um
     * utilizador. O campo `submitted_by` identifica, quando aplicável, o
     * utilizador responsável pela submissão do registo.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'edition_rankings',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('edition_id')
                    ->constrained('mt_editions')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela->string('entry_text');

                $tabela
                    ->foreignId('submitted_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela das classificações submetidas para as edições.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('edition_rankings');
    }
};
