<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das reservas de MetalThursday.
 *
 * Cada registo representa o slot correspondente a uma quinta-feira. A reserva
 * pode não possuir responsável quando não existe nenhum utilizador elegível e
 * permanece pendente até ser associada à respetiva MetalThursday.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das reservas de MetalThursday.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'reservas_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->date(
                        'data',
                    )
                    ->unique(
                        'reservas_metal_thursday_data_unica',
                    );

                $tabela
                    ->foreignId(
                        'responsavel_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->foreignId(
                        'metal_thursday_id',
                    )
                    ->nullable()
                    ->unique(
                        'reservas_metal_thursday_metal_thursday_unica',
                    )
                    ->constrained(
                        table: 'metal_thursdays',
                    )
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $tabela->timestamps();

                $tabela->index(
                    [
                        'responsavel_id',
                        'metal_thursday_id',
                        'data',
                    ],
                    'reservas_metal_thursday_responsavel_estado_data_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das reservas de MetalThursday.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'reservas_metal_thursday',
        );
    }
};
