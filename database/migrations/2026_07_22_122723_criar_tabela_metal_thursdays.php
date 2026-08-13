<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das MetalThursdays.
 *
 * Cada registo representa uma publicação pertencente a uma edição e mantém
 * o autor e o utilizador nomeado para a publicação seguinte.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das MetalThursdays.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'metal_thursdays',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string(
                        'nome',
                        255,
                    )
                    ->nullable();

                $tabela
                    ->date(
                        'data',
                    )
                    ->unique(
                        'metal_thursdays_data_unica',
                    );

                $tabela
                    ->foreignId(
                        'edicao_id',
                    )
                    ->constrained(
                        table: 'edicoes',
                    )
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $tabela
                    ->foreignId(
                        'autor_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->foreignId(
                        'proximo_nomeado_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->foreignId(
                        'criado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->foreignId(
                        'atualizado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela->timestamps();

                $tabela->softDeletes();

                $tabela->index(
                    [
                        'edicao_id',
                        'deleted_at',
                        'data',
                    ],
                    'metal_thursdays_edicao_estado_data_indice',
                );

                $tabela->index(
                    [
                        'autor_id',
                        'deleted_at',
                        'data',
                    ],
                    'metal_thursdays_autor_estado_data_indice',
                );

                $tabela->index(
                    [
                        'proximo_nomeado_id',
                        'deleted_at',
                        'data',
                    ],
                    'metal_thursdays_nomeado_estado_data_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das MetalThursdays.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'metal_thursdays',
        );
    }
};
