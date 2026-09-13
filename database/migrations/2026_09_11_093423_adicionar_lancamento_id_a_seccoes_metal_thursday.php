<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona o lançamento associado às secções das MetalThursdays.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Adiciona a associação opcional a um lançamento.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'seccoes_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela
                    ->foreignId(
                        'lancamento_id',
                    )
                    ->nullable()
                    ->after(
                        'artista_id',
                    )
                    ->constrained(
                        table: 'lancamentos',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            },
        );
    }

    /**
     * Remove a associação a lançamentos.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'seccoes_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela->dropConstrainedForeignId(
                    'lancamento_id',
                );
            },
        );
    }
};
