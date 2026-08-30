<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos rascunhos de MetalThursday.
 *
 * Cada rascunho pertence exclusivamente a uma reserva ainda em preparação.
 * A restrição única garante que uma reserva possui, no máximo, um rascunho.
 *
 * O conteúdo editável é armazenado como JSON porque um rascunho pode estar
 * incompleto e não possui os mesmos requisitos de integridade de uma
 * MetalThursday final.
 *
 * A eliminação da reserva elimina automaticamente o respetivo rascunho,
 * uma vez que este não constitui conteúdo histórico autónomo.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos rascunhos de MetalThursday.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'rascunhos_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'reserva_metal_thursday_id',
                    )
                    ->unique(
                        'rascunhos_metal_thursday_reserva_unica',
                    )
                    ->constrained(
                        table: 'reservas_metal_thursday',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela->json(
                    'dados',
                );

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela dos rascunhos de MetalThursday.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'rascunhos_metal_thursday',
        );
    }
};
