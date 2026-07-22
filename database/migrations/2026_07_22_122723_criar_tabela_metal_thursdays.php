<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das MetalThursdays.
 *
 * Cada registo representa uma publicação do MetalThursday.
 *
 * @return Migration Migração da tabela das MetalThursdays.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das MetalThursdays.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'metal_thursdays',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('nome')
                    ->nullable();

                $tabela
                    ->date('data')
                    ->unique();

                $tabela
                    ->foreignId('edicao_id')
                    ->constrained('edicoes')
                    ->restrictOnDelete();

                $tabela
                    ->foreignId('autor_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela
                    ->foreignId('proximo_nomeado_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela
                    ->foreignId('criado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela
                    ->foreignId('atualizado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();

                $tabela->index(
                    [
                        'edicao_id',
                        'data',
                    ],
                    'metal_thursdays_edicao_data_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das MetalThursdays.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('metal_thursdays');
    }
};
